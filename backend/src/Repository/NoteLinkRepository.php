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
     * @return array{linkStats: array{incoming: int, outgoing: int}, versionCount: int}
     */
    public function getNoteReadMetadata(Note $note): array
    {
        $noteId = $note->getId();
        if ($noteId === null) {
            return [
                'linkStats' => ['incoming' => 0, 'outgoing' => 0],
                'versionCount' => 0,
            ];
        }

        /** @var array{incoming: string|int, outgoing: string|int, version_count: string|int} $row */
        $row = $this->getEntityManager()->getConnection()->fetchAssociative(
            <<<'SQL'
                SELECT
                    (
                        SELECT COUNT(nl_in.id)
                        FROM note_links nl_in
                        INNER JOIN notes sn ON sn.id = nl_in.source_note_id
                        WHERE nl_in.target_note_id = :noteId
                          AND sn.deleted_at IS NULL
                    ) AS incoming,
                    (
                        SELECT COUNT(nl_out.id)
                        FROM note_links nl_out
                        INNER JOIN notes tn ON tn.id = nl_out.target_note_id
                        WHERE nl_out.source_note_id = :noteId
                          AND tn.deleted_at IS NULL
                    ) AS outgoing,
                    (
                        SELECT COUNT(nv.id)
                        FROM note_versions nv
                        WHERE nv.note_id = :noteId
                    ) AS version_count
            SQL,
            ['noteId' => $noteId->toRfc4122()],
        );

        return [
            'linkStats' => [
                'incoming' => (int) ($row['incoming'] ?? 0),
                'outgoing' => (int) ($row['outgoing'] ?? 0),
            ],
            'versionCount' => (int) ($row['version_count'] ?? 0),
        ];
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
        return $this->findLinksForNodes([$noteId], $direction, $user)[$noteId] ?? [];
    }

    /**
     * @param list<string> $noteIds
     *
     * @return array<string, list<NoteLink>>
     */
    public function findLinksForNodes(array $noteIds, string $direction, User $user): array
    {
        $noteIds = $this->filterValidNoteIds($noteIds);
        if ($noteIds === []) {
            return [];
        }

        $uuids = array_map(static fn (string $id): Uuid => Uuid::fromString($id), $noteIds);
        $indexed = array_fill_keys($noteIds, []);

        $qb = $this->createQueryBuilder('nl')
            ->innerJoin('nl.sourceNote', 'sn')
            ->innerJoin('nl.targetNote', 'tn')
            ->addSelect('sn', 'tn')
            ->andWhere('sn.user = :user')
            ->andWhere('tn.user = :user')
            ->andWhere('sn.deletedAt IS NULL')
            ->andWhere('tn.deletedAt IS NULL')
            ->setParameter('user', $user);

        if ($direction === 'outgoing') {
            $links = (clone $qb)
                ->andWhere('sn.id IN (:noteIds)')
                ->setParameter('noteIds', $uuids)
                ->getQuery()
                ->getResult();
        } elseif ($direction === 'incoming') {
            $links = (clone $qb)
                ->andWhere('tn.id IN (:noteIds)')
                ->setParameter('noteIds', $uuids)
                ->getQuery()
                ->getResult();
        } else {
            $links = (clone $qb)
                ->andWhere('sn.id IN (:noteIds) OR tn.id IN (:noteIds)')
                ->setParameter('noteIds', $uuids)
                ->getQuery()
                ->getResult();
        }

        return $this->indexLinksByNodeIds($noteIds, $links, $direction);
    }

    /**
     * @param list<string> $noteIds
     *
     * @return list<string>
     */
    private function filterValidNoteIds(array $noteIds): array
    {
        $valid = [];
        foreach ($noteIds as $noteId) {
            if (!\is_string($noteId) || !Uuid::isValid($noteId)) {
                continue;
            }
            $valid[$noteId] = $noteId;
        }

        return array_values($valid);
    }

    /**
     * @param list<string> $noteIds
     * @param list<NoteLink> $links
     *
     * @return array<string, list<NoteLink>>
     */
    private function indexLinksByNodeIds(array $noteIds, array $links, string $direction): array
    {
        $indexed = array_fill_keys($noteIds, []);
        $noteIdSet = array_fill_keys($noteIds, true);

        foreach ($links as $link) {
            $sourceId = (string) $link->getSourceNote()?->getId();
            $targetId = (string) $link->getTargetNote()?->getId();
            $linkId = (string) $link->getId();

            if (
                ($direction === 'both' || $direction === 'outgoing')
                && isset($noteIdSet[$sourceId])
            ) {
                $indexed[$sourceId][$linkId] = $link;
            }

            if (
                ($direction === 'both' || $direction === 'incoming')
                && isset($noteIdSet[$targetId])
            ) {
                $indexed[$targetId][$linkId] = $link;
            }
        }

        foreach ($indexed as $nodeId => $linksById) {
            $indexed[$nodeId] = array_values($linksById);
        }

        return $indexed;
    }
}
