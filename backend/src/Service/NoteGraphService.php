<?php

namespace App\Service;

use App\Entity\Note;
use App\Entity\NoteLink;
use App\Entity\User;
use App\Repository\NoteLinkRepository;

final class NoteGraphService
{
    public const DEFAULT_DEPTH = 1;
    public const MIN_DEPTH = 1;
    public const MAX_DEPTH = 3;
    public const MAX_NODES = 120;

    public function __construct(
        private NoteLinkRepository $noteLinkRepository,
    ) {
    }

    /**
     * @return array{
     *     nodes: list<array{id: string, title: string, folderId: string|null, isFavorite: bool}>,
     *     edges: list<array{id: string, source: string, target: string, aliases: array<int, string|null>}>,
     *     truncated: bool,
     *     frontierNodeIds: list<string>
     * }
     */
    public function buildSubgraph(Note $root, int $depth, string $direction, User $user): array
    {
        $rootId = (string) $root->getId();

        /** @var array<string, Note> $visitedNotes */
        $visitedNotes = [$rootId => $root];

        /** @var array<string, array{id: string, source: string, target: string, aliases: array<int, string|null>}> $edgesById */
        $edgesById = [];

        /** @var list<array{0: string, 1: int}> $queue */
        $queue = [[$rootId, 0]];
        $queueIndex = 0;

        $truncated = false;
        /** @var array<string, true> $frontierSet */
        $frontierSet = [];

        while ($queueIndex < count($queue)) {
            [$currentId, $currentDepth] = $queue[$queueIndex++];

            if ($currentDepth >= $depth) {
                continue;
            }

            $links = $this->noteLinkRepository->findLinksForNode($currentId, $direction, $user);

            foreach ($links as $link) {
                $neighbor = $this->resolveNeighbor($link, $currentId);
                if ($neighbor === null) {
                    continue;
                }

                $neighborId = (string) $neighbor->getId();
                $this->addEdge($edgesById, $link);

                if (isset($visitedNotes[$neighborId])) {
                    continue;
                }

                if (\count($visitedNotes) >= self::MAX_NODES) {
                    $truncated = true;
                    continue;
                }

                $visitedNotes[$neighborId] = $neighbor;
                $queue[] = [$neighborId, $currentDepth + 1];
            }
        }

        $visitedIds = array_keys($visitedNotes);

        foreach ($visitedNotes as $nodeId => $note) {
            if ($this->hasUnvisitedNeighbors($nodeId, $visitedIds, $direction, $user)) {
                $truncated = true;
                $frontierSet[$nodeId] = true;
            }
        }

        $filteredEdges = array_values(array_filter(
            $edgesById,
            static fn (array $edge): bool => \in_array($edge['source'], $visitedIds, true)
                && \in_array($edge['target'], $visitedIds, true),
        ));

        $nodes = [];
        foreach ($visitedNotes as $note) {
            $folder = $note->getFolder();
            $nodes[] = [
                'id' => (string) $note->getId(),
                'title' => $note->getTitle() ?? '',
                'folderId' => $folder !== null ? (string) $folder->getId() : null,
                'isFavorite' => $note->getIsFavorite(),
            ];
        }

        return [
            'nodes' => $nodes,
            'edges' => $filteredEdges,
            'truncated' => $truncated,
            'frontierNodeIds' => array_values(array_keys($frontierSet)),
        ];
    }

    /**
     * @param array<string, array{id: string, source: string, target: string, aliases: array<int, string|null>}> $edgesById
     */
    private function addEdge(array &$edgesById, NoteLink $link): void
    {
        $edgeId = (string) $link->getId();
        $edgesById[$edgeId] = [
            'id' => $edgeId,
            'source' => (string) $link->getSourceNote()?->getId(),
            'target' => (string) $link->getTargetNote()?->getId(),
            'aliases' => $link->getAliases(),
        ];
    }

    private function resolveNeighbor(NoteLink $link, string $currentId): ?Note
    {
        $sourceId = (string) $link->getSourceNote()?->getId();
        $targetId = (string) $link->getTargetNote()?->getId();

        if ($currentId === $sourceId) {
            return $link->getTargetNote();
        }

        if ($currentId === $targetId) {
            return $link->getSourceNote();
        }

        return null;
    }

    /**
     * @param list<string> $visitedIds
     */
    private function hasUnvisitedNeighbors(string $nodeId, array $visitedIds, string $direction, User $user): bool
    {
        $links = $this->noteLinkRepository->findLinksForNode($nodeId, $direction, $user);

        foreach ($links as $link) {
            $neighbor = $this->resolveNeighbor($link, $nodeId);
            if ($neighbor === null) {
                continue;
            }

            $neighborId = (string) $neighbor->getId();
            if (!\in_array($neighborId, $visitedIds, true)) {
                return true;
            }
        }

        return false;
    }
}
