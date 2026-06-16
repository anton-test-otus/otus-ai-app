<?php

namespace App\Tests\Unit\Service;

use App\Service\NotePreviewService;
use App\Tests\Functional\ApiTestCase;

class NotePreviewServiceTest extends ApiTestCase
{
    private NotePreviewService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = static::getContainer()->get(NotePreviewService::class);
    }

    public function testPrefetchWikiTitlesForNotesReturnsEmptyForEmptyContent(): void
    {
        $user = $this->userFactory->createUser('preview-empty@example.com');
        $note = $this->userFactory->createNote($user, 'Empty note', '');

        self::assertSame([], $this->service->prefetchWikiTitlesForNotes([$note], $user));
    }

    public function testPrefetchWikiTitlesForNotesDedupesTargetIds(): void
    {
        $user = $this->userFactory->createUser('preview-dedup@example.com');
        $target = $this->userFactory->createNote($user, 'Target Title', 'target content');
        $targetId = $target->getId()->toRfc4122();

        $noteA = $this->userFactory->createNote($user, 'Note A', 'See [[' . $targetId . ']]');
        $noteB = $this->userFactory->createNote($user, 'Note B', 'Also [[' . $targetId . ']]');

        $titlesById = $this->service->prefetchWikiTitlesForNotes([$noteA, $noteB], $user);

        self::assertSame([
            strtolower($targetId) => 'Target Title',
        ], $titlesById);
    }

    public function testPrefetchWikiTitlesForNotesIgnoresAliasOnlyLinks(): void
    {
        $user = $this->userFactory->createUser('preview-alias@example.com');
        $target = $this->userFactory->createNote($user, 'Hidden Target', 'target content');
        $targetId = $target->getId()->toRfc4122();

        $note = $this->userFactory->createNote(
            $user,
            'Alias note',
            sprintf('Custom label [[%s|Alias Label]]', $targetId),
        );

        self::assertSame([], $this->service->prefetchWikiTitlesForNotes([$note], $user));
    }
}
