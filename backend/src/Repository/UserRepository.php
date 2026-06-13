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
        $conn = $this->getEntityManager()->getConnection();
        $userId = $user->getId()->toRfc4122();

        $notesCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM notes WHERE user_id = :userId AND deleted_at IS NULL',
            ['userId' => $userId]
        );

        $foldersCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM folders WHERE user_id = :userId AND deleted_at IS NULL',
            ['userId' => $userId]
        );

        $tagsCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM tags WHERE user_id = :userId',
            ['userId' => $userId]
        );

        $lastActivity = $conn->fetchOne(
            'SELECT MAX(updated_at) FROM notes WHERE user_id = :userId',
            ['userId' => $userId]
        );

        $storageSize = (int) $conn->fetchOne(
            'SELECT COALESCE(SUM(LENGTH(content)), 0) FROM notes WHERE user_id = :userId AND deleted_at IS NULL',
            ['userId' => $userId]
        );

        return [
            'notesCount' => $notesCount,
            'foldersCount' => $foldersCount,
            'tagsCount' => $tagsCount,
            'lastActivity' => $lastActivity ? new \DateTimeImmutable($lastActivity) : null,
            'storageSize' => $storageSize,
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
