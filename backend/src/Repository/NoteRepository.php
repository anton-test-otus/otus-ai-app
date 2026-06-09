<?php

namespace App\Repository;

use App\Entity\Note;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NoteRepository extends ServiceEntityRepository
{
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
            ->leftJoin('n.tags', 't')
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

        if (!empty($criteria['tags']) && is_array($criteria['tags'])) {
            $qb->andWhere('t.id IN (:tags)')
                ->setParameter('tags', $criteria['tags']);
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
            ->groupBy('n.id')
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
