<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function searchUsers(string $query, int $page = 1, int $perPage = 20): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('LOWER(u.email) LIKE :query')
            ->setParameter('query', '%' . strtolower($query) . '%')
            ->orderBy('u.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        return $qb->getQuery()->getResult();
    }

    public function countSearchResults(string $query): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('LOWER(u.email) LIKE :query')
            ->setParameter('query', '%' . strtolower($query) . '%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getUserStatistics(User $user): array
    {
        $userId = $user->getId()?->toRfc4122();
        if ($userId === null) {
            return $this->emptyStatistics();
        }

        return $this->getUsersStatisticsBatch([$userId])[$userId] ?? $this->emptyStatistics();
    }

    /**
     * @param list<string> $userIds RFC4122 user UUIDs
     *
     * @return array<string, array{
     *     notesCount: int,
     *     foldersCount: int,
     *     tagsCount: int,
     *     lastActivity: ?\DateTimeImmutable,
     *     storageSize: int
     * }>
     */
    public function getUsersStatisticsBatch(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter($userIds)));
        if ($userIds === []) {
            return [];
        }

        $statistics = [];
        foreach ($userIds as $userId) {
            $statistics[$userId] = $this->emptyStatistics();
        }

        $conn = $this->getEntityManager()->getConnection();
        $placeholders = implode(', ', array_fill(0, count($userIds), '?'));

        $notesRows = $conn->fetchAllAssociative(
            <<<SQL
            SELECT
                user_id::text AS user_id,
                COUNT(*) FILTER (WHERE deleted_at IS NULL) AS notes_count,
                MAX(updated_at) AS last_activity,
                COALESCE(SUM(LENGTH(content)) FILTER (WHERE deleted_at IS NULL), 0) AS storage_size
            FROM notes
            WHERE user_id IN ($placeholders)
            GROUP BY user_id
            SQL,
            $userIds,
        );

        foreach ($notesRows as $row) {
            $userId = $row['user_id'];
            $statistics[$userId]['notesCount'] = (int) $row['notes_count'];
            $statistics[$userId]['storageSize'] = (int) $row['storage_size'];
            $statistics[$userId]['lastActivity'] = $row['last_activity']
                ? new \DateTimeImmutable($row['last_activity'])
                : null;
        }

        $folderRows = $conn->fetchAllAssociative(
            <<<SQL
            SELECT user_id::text AS user_id, COUNT(*) AS folders_count
            FROM folders
            WHERE user_id IN ($placeholders) AND deleted_at IS NULL
            GROUP BY user_id
            SQL,
            $userIds,
        );

        foreach ($folderRows as $row) {
            $statistics[$row['user_id']]['foldersCount'] = (int) $row['folders_count'];
        }

        $tagRows = $conn->fetchAllAssociative(
            <<<SQL
            SELECT user_id::text AS user_id, COUNT(*) AS tags_count
            FROM tags
            WHERE user_id IN ($placeholders)
            GROUP BY user_id
            SQL,
            $userIds,
        );

        foreach ($tagRows as $row) {
            $statistics[$row['user_id']]['tagsCount'] = (int) $row['tags_count'];
        }

        return $statistics;
    }

    /**
     * @return array{
     *     notesCount: int,
     *     foldersCount: int,
     *     tagsCount: int,
     *     lastActivity: ?\DateTimeImmutable,
     *     storageSize: int
     * }
     */
    private function emptyStatistics(): array
    {
        return [
            'notesCount' => 0,
            'foldersCount' => 0,
            'tagsCount' => 0,
            'lastActivity' => null,
            'storageSize' => 0,
        ];
    }

    public function countAdmins(): int
    {
        $conn = $this->getEntityManager()->getConnection();

        return (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM users WHERE roles::jsonb @> '[\"ROLE_ADMIN\"]'::jsonb"
        );
    }
}
