<?php

namespace App\DemoSeed;

final readonly class DemoNoteDefinition
{
    /**
     * @param list<string> $tags
     * @param list<DemoVersionDefinition> $versions
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $content,
        public ?string $folderPath = null,
        public array $tags = [],
        public bool $isFavorite = false,
        public array $versions = [],
        public string $updatedAtOffset = '-1 day',
    ) {
    }
}
