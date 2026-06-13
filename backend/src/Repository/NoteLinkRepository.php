<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\NoteLink;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class NoteLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoteLink::class);
    }

    /**
     * @return array{incoming: int, outgoing: int}
     */
    public function countLinkStats(Note $note): array
    {
        $outgoing = (int) $this->createQueryBuilder('nl')
            ->select('COUNT(nl.id)')
            ->innerJoin('nl.targetNote', 'tn')
            ->where('nl.sourceNote = :note')
            ->andWhere('tn.deletedAt IS NULL')
            ->setParameter('note', $note)
            ->getQuery()
            ->getSingleScalarResult();

        $incoming = (int) $this->createQueryBuilder('nl')
            ->select('COUNT(nl.id)')
            ->innerJoin('nl.sourceNote', 'sn')
            ->where('nl.targetNote = :note')
            ->andWhere('sn.deletedAt IS NULL')
            ->setParameter('note', $note)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'incoming' => $incoming,
            'outgoing' => $outgoing,
        ];
    }

    /**
     * @return NoteLink[]
     */
    public function findLinksForNode(string $noteId, string $direction, User $user): array
    {
        $links = [];

        if ($direction === 'both' || $direction === 'outgoing') {
            $links = array_merge($links, $this->findOutgoingLinksFromSource($noteId, $user));
        }

        if ($direction === 'both' || $direction === 'incoming') {
            $links = array_merge($links, $this->findIncomingLinksToTarget($noteId, $user));
        }

        return $links;
    }

    /**
     * @return NoteLink[]
     */
    private function findOutgoingLinksFromSource(string $sourceId, User $user): array
    {
        if (!Uuid::isValid($sourceId)) {
            return [];
        }

        return $this->createQueryBuilder('nl')
            ->innerJoin('nl.sourceNote', 'sn')
            ->innerJoin('nl.targetNote', 'tn')
            ->addSelect('sn', 'tn')
            ->where('sn.id = :noteId')
            ->andWhere('sn.user = :user')
            ->andWhere('tn.user = :user')
            ->andWhere('sn.deletedAt IS NULL')
            ->andWhere('tn.deletedAt IS NULL')
            ->setParameter('noteId', Uuid::fromString($sourceId))
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return NoteLink[]
     */
    private function findIncomingLinksToTarget(string $targetId, User $user): array
    {
        if (!Uuid::isValid($targetId)) {
            return [];
        }

        return $this->createQueryBuilder('nl')
            ->innerJoin('nl.sourceNote', 'sn')
            ->innerJoin('nl.targetNote', 'tn')
            ->addSelect('sn', 'tn')
            ->where('tn.id = :noteId')
            ->andWhere('sn.user = :user')
            ->andWhere('tn.user = :user')
            ->andWhere('sn.deletedAt IS NULL')
            ->andWhere('tn.deletedAt IS NULL')
            ->setParameter('noteId', Uuid::fromString($targetId))
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
