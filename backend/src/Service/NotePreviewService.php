<?php

namespace App\Service;

use App\Entity\Note;
use App\Entity\User;
use App\Repository\NoteRepository;

class NotePreviewService
{
    public const PREVIEW_MAX_LENGTH = 150;

    public const CONTEXT_WIKI_TITLES_BY_ID = 'note_preview_wiki_titles_by_id';

    public function __construct(
        private WikiLinkParser $wikiLinkParser,
        private NoteRepository $noteRepository,
    ) {
    }

    /**
     * @param iterable<Note> $notes
     *
     * @return array<string, string> lowercase note id => title
     */
    public function prefetchWikiTitlesForNotes(iterable $notes, User $user): array
    {
        $idsWithoutAlias = [];

        foreach ($notes as $note) {
            $content = $note->getContent();
            if ($content === null || $content === '') {
                continue;
            }

            foreach ($this->collectWikiLinkIdsWithoutAlias($content) as $id) {
                $idsWithoutAlias[] = $id;
            }
        }

        $idsWithoutAlias = array_values(array_unique($idsWithoutAlias));
        if ($idsWithoutAlias === []) {
            return [];
        }

        $titlesById = [];
        foreach ($this->noteRepository->findActiveByIdsForUser($idsWithoutAlias, $user) as $note) {
            $titlesById[strtolower((string) $note->getId())] = $note->getTitle();
        }

        return $titlesById;
    }

    /**
     * @param array<string, string>|null $titlesById lowercase note id => title
     */
    public function buildPreview(?string $content, ?User $user = null, ?array $titlesById = null): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        if ($titlesById === null) {
            $titlesById = $this->resolveWikiLinkTitles($content, $user);
        }

        $withoutWikiLinks = $this->wikiLinkParser->replaceForPlainText($content, $titlesById);
        $withoutHtml = preg_replace('/<[^>]*>/', ' ', $withoutWikiLinks) ?? '';
        $withoutMarkdown = preg_replace('/[#*`\[\]]/', '', $withoutHtml) ?? '';
        $plainText = trim(preg_replace('/\s+/u', ' ', $withoutMarkdown) ?? '');

        if (mb_strlen($plainText) <= self::PREVIEW_MAX_LENGTH) {
            return $plainText;
        }

        return mb_substr($plainText, 0, self::PREVIEW_MAX_LENGTH).'...';
    }

    /**
     * @return string[]
     */
    private function collectWikiLinkIdsWithoutAlias(string $content): array
    {
        $ids = [];

        foreach ($this->wikiLinkParser->parseLinksWithAliases($content) as $link) {
            $alias = $link['alias'];
            if ($alias === null || $alias === '') {
                $ids[] = $link['noteId'];
            }
        }

        return $ids;
    }

    /**
     * @return array<string, string> lowercase note id => title
     */
    private function resolveWikiLinkTitles(string $content, ?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $idsWithoutAlias = array_values(array_unique($this->collectWikiLinkIdsWithoutAlias($content)));
        if ($idsWithoutAlias === []) {
            return [];
        }

        $titlesById = [];
        foreach ($this->noteRepository->findActiveByIdsForUser($idsWithoutAlias, $user) as $note) {
            $titlesById[strtolower((string) $note->getId())] = $note->getTitle();
        }

        return $titlesById;
    }
}
