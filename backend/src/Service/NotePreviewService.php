<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\NoteRepository;

class NotePreviewService
{
    public const PREVIEW_MAX_LENGTH = 150;

    public function __construct(
        private WikiLinkParser $wikiLinkParser,
        private NoteRepository $noteRepository,
    ) {
    }

    public function buildPreview(?string $content, ?User $user = null): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        $titlesById = $this->resolveWikiLinkTitles($content, $user);
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
     * @return array<string, string> lowercase note id => title
     */
    private function resolveWikiLinkTitles(string $content, ?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $idsWithoutAlias = [];
        foreach ($this->wikiLinkParser->parseLinksWithAliases($content) as $link) {
            $alias = $link['alias'];
            if ($alias === null || $alias === '') {
                $idsWithoutAlias[] = $link['noteId'];
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
}
