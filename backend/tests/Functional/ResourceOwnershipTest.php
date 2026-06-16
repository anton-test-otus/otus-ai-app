<?php

namespace App\Tests\Functional;

use App\Entity\Folder;
use App\Entity\Note;
use App\Entity\NoteVersion;
use App\Entity\Tag;
use App\Entity\User;

class ResourceOwnershipTest extends ApiTestCase
{
    private User $userA;
    private User $userB;
    private string $tokenA;
    private string $tokenB;
    private Note $noteB;
    private Folder $folderB;
    private Tag $tagB;
    private NoteVersion $noteVersionB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = $this->userFactory->createUser('user-a@test.local');
        $this->userB = $this->userFactory->createUser('user-b@test.local');
        $this->tokenA = $this->login($this->userA);
        $this->tokenB = $this->login($this->userB);

        $this->folderB = $this->userFactory->createFolder($this->userB, 'Folder B');
        $this->tagB = $this->userFactory->createTag($this->userB, 'tag-b');
        $this->noteB = $this->userFactory->createNote(
            $this->userB,
            'Note B',
            'Content B',
            $this->folderB,
        );
        $this->noteVersionB = $this->userFactory->createNoteVersion($this->noteB);
    }

    public function testUserACannotAccessUserBNote(): void
    {
        $noteId = (string) $this->noteB->getId();

        $this->jsonRequest('GET', '/api/notes/' . $noteId, null, $this->tokenA);
        self::assertResponseStatusCodeSame(404);

        $this->jsonRequest('PUT', '/api/notes/' . $noteId, [
            'title' => 'Hacked',
            'content' => 'Hacked content',
        ], $this->tokenA);
        self::assertResponseStatusCodeSame(404);

        $this->jsonRequest('PATCH', '/api/notes/' . $noteId, [
            'title' => 'Hacked',
        ], $this->tokenA, [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ]);
        self::assertResponseStatusCodeSame(404);

        $this->jsonRequest('DELETE', '/api/notes/' . $noteId, null, $this->tokenA);
        self::assertResponseStatusCodeSame(404);

        $this->entityManager->refresh($this->noteB);
        self::assertSame('Note B', $this->noteB->getTitle());
        self::assertSame('Content B', $this->noteB->getContent());
    }

    public function testUserACannotAccessUserBFolder(): void
    {
        $folderId = (string) $this->folderB->getId();

        $this->jsonRequest('GET', '/api/folders/' . $folderId, null, $this->tokenA);
        self::assertResponseStatusCodeSame(404);

        $this->jsonRequest('PUT', '/api/folders/' . $folderId, [
            'name' => 'Hacked folder',
        ], $this->tokenA);
        self::assertResponseStatusCodeSame(404);

        $this->jsonRequest('PATCH', '/api/folders/' . $folderId, [
            'name' => 'Hacked folder',
        ], $this->tokenA, [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ]);
        self::assertResponseStatusCodeSame(404);

        $this->jsonRequest('DELETE', '/api/folders/' . $folderId, null, $this->tokenA);
        self::assertResponseStatusCodeSame(404);
    }

    public function testUserACannotAccessUserBTag(): void
    {
        $tagId = (string) $this->tagB->getId();

        $this->jsonRequest('GET', '/api/tags/' . $tagId, null, $this->tokenA);
        self::assertResponseStatusCodeSame(404);

        $this->jsonRequest('PUT', '/api/tags/' . $tagId, [
            'name' => 'hacked-tag',
        ], $this->tokenA);
        self::assertResponseStatusCodeSame(404);

        $this->jsonRequest('DELETE', '/api/tags/' . $tagId, null, $this->tokenA);
        self::assertResponseStatusCodeSame(404);
    }

    public function testUserACannotAccessUserBNoteVersion(): void
    {
        $versionId = (string) $this->noteVersionB->getId();

        $this->jsonRequest('GET', '/api/note_versions/' . $versionId, null, $this->tokenA);
        self::assertResponseStatusCodeSame(404);
    }

    public function testOwnerBCanReadNoteAndFolder(): void
    {
        $noteId = (string) $this->noteB->getId();
        $folderId = (string) $this->folderB->getId();

        $this->jsonRequest('GET', '/api/notes/' . $noteId, null, $this->tokenB);
        self::assertResponseIsSuccessful();
        $note = $this->responseJson();
        self::assertSame('Note B', $note['title']);

        $this->jsonRequest('GET', '/api/folders/' . $folderId, null, $this->tokenB);
        self::assertResponseIsSuccessful();
        $folder = $this->responseJson();
        self::assertSame('Folder B', $folder['name']);
    }

    public function testOwnerBSoftDeleteThenPermanentDeleteNote(): void
    {
        $noteId = (string) $this->noteB->getId();

        $this->jsonRequest('DELETE', '/api/notes/' . $noteId, null, $this->tokenB);
        self::assertResponseIsSuccessful();

        $this->jsonRequest('GET', '/api/notes/' . $noteId, null, $this->tokenB);
        self::assertResponseStatusCodeSame(404);

        $this->jsonRequest('DELETE', '/api/notes/' . $noteId, null, $this->tokenB);
        self::assertResponseIsSuccessful();

        $this->entityManager->clear();
        self::assertNull($this->entityManager->find(Note::class, $this->noteB->getId()));
    }

    public function testNotesCollectionForUserAExcludesUserBNote(): void
    {
        $this->jsonRequest('GET', '/api/notes', null, $this->tokenA);
        self::assertResponseIsSuccessful();

        $noteBId = (string) $this->noteB->getId();
        $collection = $this->responseJson();
        $members = $collection['hydra:member'] ?? $collection['member'] ?? $collection;

        self::assertIsArray($members);

        foreach ($members as $item) {
            if (!is_array($item)) {
                continue;
            }

            $itemId = $item['id'] ?? null;
            if (is_string($itemId) && str_contains($itemId, '/')) {
                $itemId = basename($itemId);
            }

            self::assertNotSame($noteBId, $itemId);
        }
    }
}
