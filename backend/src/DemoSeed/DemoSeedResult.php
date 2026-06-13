<?php

namespace App\DemoSeed;

use App\Entity\User;

final readonly class DemoSeedResult
{
    public function __construct(
        public User $user,
        public int $folderCount,
        public int $tagCount,
        public int $noteCount,
        public int $linkCount,
        public int $versionCount,
        public int $favoriteCount,
    ) {
    }
}
