<?php

namespace App\Service;

class WikiLinkParser
{
    private const UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

    private static function wikiLinkPattern(): string
    {
        static $pattern = null;

        if ($pattern === null) {
            $escapedPrefix = preg_quote('\[\[', '/');
            $pattern = '/(?:'.$escapedPrefix.'|\[\[)('.self::UUID.')(?:\|([^\]]+))?\]\]/i';
        }

        return $pattern;
    }

    /**
     * @return string[] UUID целевых заметок (lowercase, unique)
     */
    public function parseLinks(string $content): array
    {
        $ids = [];

        if (preg_match_all(self::wikiLinkPattern(), $content, $matches)) {
            foreach ($matches[1] as $id) {
                $normalized = strtolower(trim($id));
                if ($normalized !== '') {
                    $ids[] = $normalized;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<int, array{noteId: string, alias: string|null, raw: string}>
     */
    public function parseLinksWithAliases(string $content): array
    {
        $links = [];

        if (preg_match_all(self::wikiLinkPattern(), $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $noteId = strtolower(trim($match[1]));
                if ($noteId === '') {
                    continue;
                }

                $links[] = [
                    'noteId' => $noteId,
                    'alias' => isset($match[2]) ? trim($match[2]) : null,
                    'raw' => $match[0],
                ];
            }
        }

        return $links;
    }

    /**
     * @param array<string, string> $titlesById lowercase note id => title
     */
    public function replaceForPlainText(string $content, array $titlesById = []): string
    {
        return preg_replace_callback(
            self::wikiLinkPattern(),
            static function (array $match) use ($titlesById): string {
                $noteId = strtolower(trim($match[1]));
                $alias = isset($match[2]) ? trim($match[2]) : '';

                if ($alias !== '') {
                    return $alias;
                }

                return $titlesById[$noteId] ?? '';
            },
            $content,
        ) ?? $content;
    }
}
