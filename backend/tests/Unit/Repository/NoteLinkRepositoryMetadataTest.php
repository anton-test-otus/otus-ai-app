<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Note;
use App\Entity\NoteLink;
use App\Repository\NoteLinkRepository;
use App\Tests\Functional\ApiTestCase;

class NoteLinkRepositoryMetadataTest extends ApiTestCase
{
    private NoteLinkRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->entityManager->getRepository(NoteLink::class);
        self::assertInstanceOf(NoteLinkRepository::class, $this->repository);
    }

    public function testGetNoteReadMetadataReturnsZerosForNewNote(): void
    {
        $user = $this->userFactory->createUser('metadata-empty@example.com');
        $note = $this->userFactory->createNote($user, 'Solo note', 'content');

        self::assertSame([
            'linkStats' => ['incoming' => 0, 'outgoing' => 0],
            'versionCount' => 0,
        ], $this->repository->getNoteReadMetadata($note));
    }

    public function testGetNoteReadMetadataCountsOutgoingLinks(): void
    {
        $user = $this->userFactory->createUser('metadata-out@example.com');
        $source = $this->userFactory->createNote($user, 'Source', 'source content');
        $target = $this->userFactory->createNote($user, 'Target', 'target content');

        $this->persistLink($source, $target);

        $metadata = $this->repository->getNoteReadMetadata($source);

        self::assertSame(['incoming' => 0, 'outgoing' => 1], $metadata['linkStats']);
        self::assertSame(0, $metadata['versionCount']);
    }

    public function testGetNoteReadMetadataCountsIncomingLinks(): void
    {
        $user = $this->userFactory->createUser('metadata-in@example.com');
        $source = $this->userFactory->createNote($user, 'Source', 'source content');
        $target = $this->userFactory->createNote($user, 'Target', 'target content');

        $this->persistLink($source, $target);

        $metadata = $this->repository->getNoteReadMetadata($target);

        self::assertSame(['incoming' => 1, 'outgoing' => 0], $metadata['linkStats']);
        self::assertSame(0, $metadata['versionCount']);
    }

    public function testGetNoteReadMetadataCountsVersions(): void
    {
        $user = $this->userFactory->createUser('metadata-versions@example.com');
        $note = $this->userFactory->createNote($user, 'Versioned', 'v1');
        $this->userFactory->createNoteVersion($note, 'v2');
        $this->userFactory->createNoteVersion($note, 'v3');

        $metadata = $this->repository->getNoteReadMetadata($note);

        self::assertSame(['incoming' => 0, 'outgoing' => 0], $metadata['linkStats']);
        self::assertSame(2, $metadata['versionCount']);
    }

    public function testGetNoteReadMetadataIgnoresLinksToDeletedNotes(): void
    {
        $user = $this->userFactory->createUser('metadata-deleted@example.com');
        $source = $this->userFactory->createNote($user, 'Source', 'source content');
        $deletedTarget = $this->userFactory->createNote($user, 'Deleted target', 'target content');

        $this->persistLink($source, $deletedTarget);
        $this->userFactory->softDeleteNote($deletedTarget);

        $metadata = $this->repository->getNoteReadMetadata($source);

        self::assertSame(['incoming' => 0, 'outgoing' => 0], $metadata['linkStats']);
    }

    private function persistLink(Note $source, Note $target): void
    {
        $link = new NoteLink();
        $link->setSourceNote($source);
        $link->setTargetNote($target);

        $this->entityManager->persist($link);
        $this->entityManager->flush();
    }
}
