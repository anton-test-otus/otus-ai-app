<?php

namespace App\Tests\Functional;

use App\Entity\Folder;
use App\Entity\Note;
use App\Entity\NoteLink;
use App\Entity\User;

class NotePatchSyncTest extends ApiTestCase
{
    private User $userA;
    private Folder $folderA;
    private Note $noteA;
    private Note $noteTarget;
    private Note $noteOther;
    private string $tokenA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = $this->userFactory->createUser('patch-sync@example.com');
        $this->folderA = $this->userFactory->createFolder($this->userA, 'Folder A');
        $this->noteTarget = $this->userFactory->createNote($this->userA, 'Target', 'target content');
        $this->noteOther = $this->userFactory->createNote($this->userA, 'Other', 'other content');
        $this->noteA = $this->userFactory->createNote($this->userA, 'Note A', 'plain content', $this->folderA);
        $this->tokenA = $this->login($this->userA);

        $targetId = (string) $this->noteTarget->getId();
        $this->jsonRequest('PUT', '/api/notes/' . $this->noteA->getId(), [
            'title' => 'Note A',
            'content' => 'Link [[' . $targetId . ']]',
        ], $this->tokenA);
        self::assertResponseIsSuccessful();
    }

    public function testPatchIsFavoriteOnlyDoesNotChangeNoteLinks(): void
    {
        $linksBefore = $this->countNoteLinks();

        $this->mergePatch('/api/notes/' . $this->noteA->getId(), [
            'isFavorite' => true,
        ], $this->tokenA);

        self::assertResponseStatusCodeSame(200);
        self::assertSame($linksBefore, $this->countNoteLinks());
    }

    public function testPatchFolderOnlyDoesNotChangeNoteLinks(): void
    {
        $linksBefore = $this->countNoteLinks();

        $this->mergePatch('/api/notes/' . $this->noteA->getId(), [
            'folder' => '/api/folders/' . $this->folderA->getId(),
        ], $this->tokenA);

        self::assertResponseStatusCodeSame(200);
        self::assertSame($linksBefore, $this->countNoteLinks());
    }

    public function testPutContentChangeUpdatesWikiLinks(): void
    {
        $otherId = (string) $this->noteOther->getId();

        $this->jsonRequest('PUT', '/api/notes/' . $this->noteA->getId(), [
            'title' => 'Note A',
            'content' => 'Now [[' . $otherId . ']]',
        ], $this->tokenA);
        self::assertResponseStatusCodeSame(200);

        $this->jsonRequest('GET', '/api/notes/' . $this->noteA->getId(), null, $this->tokenA);
        self::assertResponseStatusCodeSame(200);
        $data = $this->responseJson();

        self::assertArrayHasKey('linkStats', $data);
        self::assertSame(1, $data['linkStats']['outgoing']);

        $links = $this->entityManager->getRepository(NoteLink::class)->findAll();
        self::assertCount(1, $links);
        self::assertTrue($links[0]->getTargetNote()->getId()->equals($this->noteOther->getId()));
    }

    private function countNoteLinks(): int
    {
        return $this->entityManager->getRepository(NoteLink::class)->count([]);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function mergePatch(string $uri, array $body, string $token): void
    {
        $this->jsonRequest('PATCH', $uri, $body, $token, [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ]);
    }
}
