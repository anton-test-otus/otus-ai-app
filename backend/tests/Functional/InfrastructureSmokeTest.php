<?php

namespace App\Tests\Functional;

use App\Entity\User;

class InfrastructureSmokeTest extends ApiTestCase
{
    public function testKernelBootsWithEmptyDatabase(): void
    {
        self::assertSame(0, $this->entityManager->getRepository(User::class)->count([]));
    }
}
