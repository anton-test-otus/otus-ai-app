<?php

namespace App\DemoSeed;

final readonly class DemoUniverseDefinition
{
    public const DEMO_PASSWORD = 'demo1234';

    /**
     * @param list<string> $roles
     * @param list<string> $folders
     * @param list<string> $tags
     * @param list<DemoNoteDefinition> $notes
     */
    public function __construct(
        public string $email,
        public array $roles,
        public array $folders,
        public array $tags,
        public array $notes,
    ) {
    }
}
