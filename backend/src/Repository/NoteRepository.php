<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\User;
use App\Security\AuthenticatedUserAssert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class NoteRepository extends ServiceEntityRepository
{
    private const FTS_CONFIG = 'russian';

    /** Minimum token length for prefix match (`:*`); shorter tokens are ignored. */
    private const FTS_MIN_PREFIX_LENGTH = 3;

    /**
     * List/search queries filter by user and active notes (`deletedAt IS NULL`).
     * Partial indexes `notes_user_active_updated_idx` and `notes_user_favorite_active_updated_idx`
     * cover dashboard and favorites ordering by `updatedAt`.
     *
     * Full-text search (`search()`, `GET /api/notes/search`) uses PostgreSQL `search_vector`
     * (`to_tsvector` + GIN) and `to_tsquery` with prefix `:*` per token. Title-only filter
     * on `GET /api/notes?title=` (wiki link picker) stays on API Platform SearchFilter (`ILIKE`).
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    public function search(User $user, array $criteria, int $page = 1, int $perPage = 20): array
    {
        $user = AuthenticatedUserAssert::requirePersistedUser($user);

        $qb = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.deletedAt IS NULL')
            ->setParameter('user', $user);

        if (is_string($criteria['query'] ?? null)) {
            $query = trim($criteria['query']);
            if ($query !== '') {
                $matchingIds = $this->findActiveNoteIdsMatchingFullText($user, $query);
                if ($matchingIds === []) {
                    return ['notes' => [], 'total' => 0];
                }

                $qb->andWhere('n.id IN (:ftsNoteIds)')
                    ->setParameter('ftsNoteIds', $matchingIds);
            }
        }

        if (!empty($criteria['folderId'])) {
            $qb->andWhere('n.folder = :folderId')
                ->setParameter('folderId', $criteria['folderId']);
        }

        if (array_key_exists('isFavorite', $criteria) && $criteria['isFavorite'] !== null) {
            $qb->andWhere('n.isFavorite = :isFavorite')
                ->setParameter('isFavorite', (bool) $criteria['isFavorite']);
        }

        $tagIds = $this->normalizeTagIds($criteria['tags'] ?? []);
        foreach ($tagIds as $index => $tagId) {
            $alias = 'filterTag' . $index;
            $qb->innerJoin('n.tags', $alias)
                ->andWhere($alias . '.id = :filterTagId' . $index)
                ->setParameter('filterTagId' . $index, $tagId);
        }

        if ($criteria['dateFrom'] instanceof \DateTimeImmutable) {
            $qb->andWhere('n.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $criteria['dateFrom']);
        }

        if ($criteria['dateTo'] instanceof \DateTimeImmutable) {
            $qb->andWhere('n.createdAt <= :dateTo')
                ->setParameter('dateTo', $criteria['dateTo']);
        }

        $countQb = clone $qb;
        $total = $countQb->select('COUNT(DISTINCT n.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $notes = $qb
            ->orderBy('n.updatedAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return [
            'notes' => $notes,
            'total' => $total,
        ];
    }

    /**
     * @return string[]
     */
    private function normalizeTagIds(mixed $tags): array
    {
        if (!is_array($tags)) {
            return [];
        }

        return array_values(array_unique(array_filter($tags, static fn ($id) => is_string($id) && $id !== '')));
    }

    /**
     * @return list<Uuid>
     */
    private function findActiveNoteIdsMatchingFullText(User $user, string $query): array
    {
        $tsQuery = $this->buildPrefixTsQuery($query);
        if ($tsQuery === null) {
            return [];
        }

        try {
            $rows = $this->getEntityManager()->getConnection()->fetchFirstColumn(
                <<<'SQL'
                    SELECT id
                    FROM notes
                    WHERE user_id = :userId
                      AND deleted_at IS NULL
                      AND search_vector @@ to_tsquery(:config, :tsquery)
                SQL,
                [
                    'userId' => $user->getId()->toRfc4122(),
                    'config' => self::FTS_CONFIG,
                    'tsquery' => $tsQuery,
                ],
                [
                    'userId' => 'uuid',
                ],
            );
        } catch (\Doctrine\DBAL\Exception) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            if (is_string($row) && Uuid::isValid($row)) {
                $ids[] = Uuid::fromString($row);
            }
        }

        return $ids;
    }

    private function buildPrefixTsQuery(string $query): ?string
    {
        $tokens = preg_split('/\s+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($tokens) || $tokens === []) {
            return null;
        }

        $parts = [];
        foreach ($tokens as $token) {
            $token = preg_replace('/[&|!():*\'"\\\\]+/u', '', $token) ?? '';
            if ($token === '' || mb_strlen($token) < self::FTS_MIN_PREFIX_LENGTH) {
                continue;
            }

            $parts[] = $this->quoteTsQueryLexeme($token) . ':*';
        }

        if ($parts === []) {
            return null;
        }

        return implode(' & ', $parts);
    }

    private function quoteTsQueryLexeme(string $token): string
    {
        $token = str_replace("'", "''", $token);

        if (preg_match('/^[\p{L}\p{N}_-]+$/u', $token) === 1) {
            return $token;
        }

        return "'" . $token . "'";
    }

    public function findDeletedNotes($user, int $page = 1, int $perPage = 20): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.deletedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('n.deletedAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countDeletedNotes($user): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :user')
            ->andWhere('n.deletedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Note[]
     */
    public function findAllDeletedByUser($user): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.deletedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string[] $ids
     * @return Note[]
     */
    public function findActiveByIdsForUser(array $ids, $user): array
    {
        if ($ids === []) {
            return [];
        }

        $uuidIds = [];
        foreach ($ids as $id) {
            if (!Uuid::isValid($id)) {
                continue;
            }
            $uuidIds[] = Uuid::fromString($id);
        }

        if ($uuidIds === []) {
            return [];
        }

        return $this->createQueryBuilder('n')
            ->where('n.id IN (:ids)')
            ->andWhere('n.user = :user')
            ->andWhere('n.deletedAt IS NULL')
            ->setParameter('ids', $uuidIds)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findOneActiveByIdForUser(string $id, $user): ?Note
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->createQueryBuilder('n')
            ->where('n.id = :id')
            ->andWhere('n.user = :user')
            ->andWhere('n.deletedAt IS NULL')
            ->setParameter('id', Uuid::fromString($id))
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
