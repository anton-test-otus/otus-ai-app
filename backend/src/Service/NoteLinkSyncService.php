<?php

namespace App\Service;

use App\Entity\Note;
use App\Entity\NoteLink;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;

class NoteLinkSyncService
{
    public function __construct(
        private EntityManagerInterface $em,
        private WikiLinkParser $wikiLinkParser,
        private NoteRepository $noteRepository,
    ) {
    }

    public function syncFromContent(Note $note): void
    {
        $user = $note->getUser();
        if ($user === null) {
            return;
        }

        $groups = $this->groupAliasesByTarget(
            $this->wikiLinkParser->parseLinksWithAliases($note->getContent() ?? ''),
        );

        $sourceId = $note->getId() !== null ? strtolower((string) $note->getId()) : null;
        if ($sourceId !== null) {
            unset($groups[$sourceId]);
        }

        if ($groups === []) {
            $this->removeAllOutgoingLinks($note);
            $this->em->flush();

            return;
        }

        $notesById = [];
        foreach ($this->noteRepository->findActiveByIdsForUser(array_keys($groups), $user) as $targetNote) {
            $notesById[strtolower((string) $targetNote->getId())] = $targetNote;
        }

        $validGroups = [];
        foreach ($groups as $targetId => $aliases) {
            if (isset($notesById[$targetId])) {
                $validGroups[$targetId] = $aliases;
            }
        }

        $existingByTargetId = [];
        foreach ($note->getOutgoingLinks() as $link) {
            $target = $link->getTargetNote();
            if ($target === null) {
                continue;
            }

            $existingByTargetId[strtolower((string) $target->getId())] = $link;
        }

        foreach ($validGroups as $targetId => $aliases) {
            $existingLink = $existingByTargetId[$targetId] ?? null;
            if ($existingLink !== null) {
                $existingLink->setAliases($aliases);
                unset($existingByTargetId[$targetId]);

                continue;
            }

            $link = new NoteLink();
            $link->setSourceNote($note);
            $link->setTargetNote($notesById[$targetId]);
            $link->setAliases($aliases);
            $this->em->persist($link);
            $note->addOutgoingLink($link);
        }

        foreach ($existingByTargetId as $link) {
            $this->em->remove($link);
            $note->removeOutgoingLink($link);
        }

        $this->em->flush();
    }

    /**
     * @param array<int, array{noteId: string, alias: string|null, raw: string}> $parsedLinks
     *
     * @return array<string, array<int, string|null>>
     */
    private function groupAliasesByTarget(array $parsedLinks): array
    {
        $groups = [];

        foreach ($parsedLinks as $link) {
            $targetId = $link['noteId'];
            if (!isset($groups[$targetId])) {
                $groups[$targetId] = [];
            }

            $groups[$targetId][] = $this->normalizeAlias($link['alias']);
        }

        return $groups;
    }

    private function normalizeAlias(?string $alias): ?string
    {
        if ($alias === null) {
            return null;
        }

        $alias = trim($alias);

        return $alias === '' ? null : $alias;
    }

    private function removeAllOutgoingLinks(Note $note): void
    {
        foreach ($note->getOutgoingLinks() as $link) {
            $this->em->remove($link);
        }
        $note->getOutgoingLinks()->clear();
    }
}
