<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\NoteVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NoteVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoteVersion::class);
    }

    /**
     * Получение версий для конкретной заметки
     */
    public function findByNote(Note $note, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.note = :note')
            ->setParameter('note', $note)
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Получение последней версии заметки
     */
    public function findLastVersionForNote(Note $note): ?NoteVersion
    {
        return $this->createQueryBuilder('v')
            ->where('v.note = :note')
            ->setParameter('note', $note)
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Подсчет версий для заметки
     */
    public function countByNote(Note $note): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.note = :note')
            ->setParameter('note', $note)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Версии сверх лимита (самые старые), отсортированы от новых к старым
     */
    public function findExcessVersions(Note $note, int $maxVersions): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.note = :note')
            ->setParameter('note', $note)
            ->orderBy('v.createdAt', 'DESC')
            ->setFirstResult($maxVersions)
            ->getQuery()
            ->getResult();
    }
}
