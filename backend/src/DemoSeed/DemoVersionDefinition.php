<?php

namespace App\DemoSeed;

final readonly class DemoVersionDefinition
{
    public function __construct(
        public string $title,
        public string $content,
        public string $createdAtOffset,
    ) {
    }
}
