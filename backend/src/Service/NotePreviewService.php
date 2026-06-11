<?php

namespace App\Service;

class NotePreviewService
{
    public const PREVIEW_MAX_LENGTH = 150;

    public static function buildPreview(?string $content): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        $withoutHtml = preg_replace('/<[^>]*>/', ' ', $content) ?? '';
        $withoutMarkdown = preg_replace('/[#*`\[\]]/', '', $withoutHtml) ?? '';
        $plainText = trim(preg_replace('/\s+/u', ' ', $withoutMarkdown) ?? '');

        if (mb_strlen($plainText) <= self::PREVIEW_MAX_LENGTH) {
            return $plainText;
        }

        return mb_substr($plainText, 0, self::PREVIEW_MAX_LENGTH).'...';
    }
}
