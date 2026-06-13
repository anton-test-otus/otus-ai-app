<?php

namespace App\DemoSeed;

use App\Entity\Note;

final class DemoLinkPlaceholderResolver
{
    /**
     * @param array<string, Note> $notesByKey
     */
    public static function resolve(string $content, array $notesByKey): string
    {
        $resolved = preg_replace_callback(
            '/\{\{link:([a-z0-9_-]+)(?:\|([^}]+))?\}\}/',
            static function (array $matches) use ($notesByKey): string {
                $key = $matches[1];
                if (!isset($notesByKey[$key])) {
                    throw new \InvalidArgumentException(sprintf('Неизвестный ключ wiki-ссылки: %s', $key));
                }

                $uuid = (string) $notesByKey[$key]->getId();
                $alias = $matches[2] ?? null;

                if ($alias !== null && $alias !== '') {
                    return sprintf('[[%s|%s]]', $uuid, $alias);
                }

                return sprintf('[[%s]]', $uuid);
            },
            $content,
        );

        if ($resolved === null) {
            throw new \RuntimeException('Не удалось разрешить плейсхолдеры wiki-ссылок');
        }

        if (preg_match('/\{\{link:[^}]+\}\}/', $resolved) === 1) {
            throw new \RuntimeException('В контенте остались неразрешённые плейсхолдеры wiki-ссылок');
        }

        return $resolved;
    }
}
