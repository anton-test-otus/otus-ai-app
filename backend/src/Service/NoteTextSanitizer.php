<?php

namespace App\Service;

use App\Entity\Note;

final class NoteTextSanitizer
{
    public function sanitizeNote(Note $note): void
    {
        $note->setTitle($this->sanitize($note->getTitle()));
        $note->setContent($this->sanitize($note->getContent()));
    }

    /** Нормализует текст заметки: nbsp, zero-width и прочие нечитаемые символы. */
    private function sanitize(string $text): string
    {
        $text = str_replace(["\u{00a0}", "\u{202f}", "\u{2007}"], ' ', $text);
        $text = preg_replace('/[\x{200b}-\x{200d}\x{2060}\x{feff}]/u', '', $text) ?? $text;
        $text = preg_replace('/[\x{0000}-\x{0008}\x{000b}\x{000c}\x{000e}-\x{001f}\x{007f}]/u', '', $text) ?? $text;

        return $text;
    }
}
