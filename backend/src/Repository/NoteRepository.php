<?php

namespace App\Repository;

use App\Entity\Note;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class NoteRepository extends ServiceEntityRepository
{
    /**
     * List/search queries filter by user and active notes (`deletedAt IS NULL`).
     * Partial indexes `notes_user_active_updated_idx` and `notes_user_favorite_active_updated_idx`
     * cover dashboard and favorites ordering by `updatedAt`.
     *
     * Full-text search (`search()`, API Platform SearchFilter on title/content) uses
     * `LIKE '%…%'` — no index in MVP; PostgreSQL GIN + `to_tsvector` is a follow-up.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    public function findByUserWithPagination(string $userId, int $page = 1, int $perPage = 20): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.user = :userId')
            ->andWhere('n.deletedAt IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('n.updatedAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        return $qb->getQuery()->getResult();
    }

    public function countByUser(string $userId): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :userId')
            ->andWhere('n.deletedAt IS NULL')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function search($user, array $criteria, int $page = 1, int $perPage = 20): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.deletedAt IS NULL')
            ->setParameter('user', $user);

        if (!empty($criteria['query'])) {
            $qb->andWhere('(n.title LIKE :query OR n.content LIKE :query)')
                ->setParameter('query', '%' . $criteria['query'] . '%');
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

    /**
     * Find notes by title (case-insensitive) for a specific user
     * Returns active notes only (not deleted)
     * 
     * @return Note[]
     */
    public function findByTitleCaseInsensitive(string $title, $user): array
    {
        return $this->createQueryBuilder('n')
            ->where('LOWER(n.title) = LOWER(:title)')
            ->andWhere('n.user = :user')
            ->andWhere('n.deletedAt IS NULL')
            ->setParameter('title', $title)
            ->setParameter('user', $user)
            ->orderBy('n.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find notes that link to the given note (backlinks)
     * Returns notes that have wiki-links pointing to this note
     * 
     * @return Note[]
     */
    public function findBacklinks(Note $note): array
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('n.outgoingLinks', 'nl')
            ->where('nl.targetNote = :note')
            ->andWhere('n.deletedAt IS NULL')
            ->setParameter('note', $note)
            ->orderBy('n.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
