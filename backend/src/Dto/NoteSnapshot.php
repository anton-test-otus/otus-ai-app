<?php

namespace App\Dto;

use App\Entity\Note;
use App\Entity\NoteVersion;

final readonly class NoteSnapshot
{
    public function __construct(
        public string $title,
        public string $content,
    ) {
    }

    public static function fromNote(Note $note): self
    {
        return new self(
            (string) $note->getTitle(),
            (string) $note->getContent(),
        );
    }

    public static function fromVersion(NoteVersion $version): self
    {
        return new self(
            (string) $version->getTitle(),
            (string) $version->getContent(),
        );
    }

    public function equals(self $other): bool
    {
        return $this->title === $other->title
            && $this->content === $other->content;
    }
}
