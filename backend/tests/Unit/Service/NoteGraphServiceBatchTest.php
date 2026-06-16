<?php

namespace App\Tests\Unit\Service;

use App\Entity\Note;
use App\Entity\User;
use App\Repository\NoteLinkRepository;
use App\Service\NoteGraphService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class NoteGraphServiceBatchTest extends TestCase
{
    #[AllowMockObjectsWithoutExpectations]
    public function testBuildSubgraphCallsFindLinksForNodesAtMostDepthPlusOneTimes(): void
    {
        $callCount = 0;
        $repository = $this->createMock(NoteLinkRepository::class);
        $repository->method('findLinksForNodes')
            ->willReturnCallback(function (array $noteIds) use (&$callCount): array {
                ++$callCount;

                $indexed = [];
                foreach ($noteIds as $noteId) {
                    $indexed[$noteId] = [];
                }

                return $indexed;
            });

        $service = new NoteGraphService($repository);
        $root = $this->createConfiguredMock(Note::class, [
            'getId' => Uuid::v4(),
        ]);
        $user = $this->createMock(User::class);

        foreach ([1, 2, 3] as $depth) {
            $callCount = 0;
            $service->buildSubgraph($root, $depth, 'both', $user);
            self::assertLessThanOrEqual($depth + 1, $callCount, sprintf('depth=%d', $depth));
        }
    }
}
