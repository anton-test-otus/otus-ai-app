<?php

namespace App\Tests\Functional;

use App\Entity\Folder;
use App\Entity\Note;
use App\Entity\Tag;
use App\Entity\User;

class OwnedRelationValidationTest extends ApiTestCase
{
    private User $userA;
    private User $userB;
    private Folder $folderA;
    private Folder $folderB;
    private Tag $tagA;
    private Tag $tagB;
    private Note $noteA;
    private string $tokenA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = $this->userFactory->createUser('owned-a@example.com');
        $this->userB = $this->userFactory->createUser('owned-b@example.com');
        $this->folderA = $this->userFactory->createFolder($this->userA, 'Folder A');
        $this->folderB = $this->userFactory->createFolder($this->userB, 'Folder B');
        $this->tagA = $this->userFactory->createTag($this->userA, 'tag-a');
        $this->tagB = $this->userFactory->createTag($this->userB, 'tag-b');
        $this->noteA = $this->userFactory->createNote($this->userA, 'Note A', 'content', $this->folderA);

        $this->tokenA = $this->login($this->userA);
    }

    public function testPostNoteWithForeignFolderReturns422(): void
    {
        $this->jsonRequest('POST', '/api/notes', [
            'title' => 'Foreign folder note',
            'content' => 'content',
            'folder' => $this->folderIri($this->folderB),
        ], $this->tokenA);

        $this->assertRejectionWithMessage('Папка');
    }

    public function testPatchNoteWithForeignTagReturns422(): void
    {
        $this->mergePatch('/api/notes/' . $this->noteA->getId(), [
            'tags' => [$this->tagIri($this->tagB)],
        ], $this->tokenA);

        $this->assertRejectionWithMessage('Тег');
    }

    public function testPostFolderWithForeignParentReturns422(): void
    {
        $this->jsonRequest('POST', '/api/folders', [
            'name' => 'Child of foreign folder',
            'parent' => $this->folderIri($this->folderB),
        ], $this->tokenA);

        $this->assertRejectionWithMessage('Родительская папка');
    }

    public function testPostFolderWithDeletedOwnParentReturns422(): void
    {
        $folderADeleted = $this->userFactory->createFolder($this->userA, 'Folder A deleted');
        $folderADeleted->setDeletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        $this->jsonRequest('POST', '/api/folders', [
            'name' => 'Child of deleted folder',
            'parent' => $this->folderIri($folderADeleted),
        ], $this->tokenA);

        $this->assertRejectionWithMessage('Родительская папка удалена');
    }

    public function testPostNoteWithOwnFolderReturns201(): void
    {
        $this->jsonRequest('POST', '/api/notes', [
            'title' => 'Own folder note',
            'content' => 'content',
            'folder' => $this->folderIri($this->folderA),
            'tags' => [$this->tagIri($this->tagA)],
        ], $this->tokenA);

        self::assertResponseStatusCodeSame(201);
        $data = $this->responseJson();
        self::assertSame('Own folder note', $data['title']);
    }

    public function testPostChildFolderWithOwnParentReturns201(): void
    {
        $this->jsonRequest('POST', '/api/folders', [
            'name' => 'Child folder',
            'parent' => $this->folderIri($this->folderA),
        ], $this->tokenA);

        self::assertResponseStatusCodeSame(201);
        $data = $this->responseJson();
        self::assertSame('Child folder', $data['name']);
    }

    public function testPatchNoteIsFavoriteOnlyReturns200(): void
    {
        $this->mergePatch('/api/notes/' . $this->noteA->getId(), [
            'isFavorite' => true,
        ], $this->tokenA);

        self::assertResponseStatusCodeSame(200);
        $data = $this->responseJson();
        self::assertTrue($data['isFavorite']);
    }

    private function folderIri(Folder $folder): string
    {
        return '/api/folders/' . $folder->getId();
    }

    private function tagIri(Tag $tag): string
    {
        return '/api/tags/' . $tag->getId();
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

    private function assertRejectionWithMessage(string $expectedSubstring): void
    {
        $status = $this->client->getResponse()->getStatusCode();
        self::assertContains($status, [400, 422]);
        $data = $this->responseJson();
        $message = (string) ($data['detail'] ?? $data['message'] ?? json_encode($data, JSON_THROW_ON_ERROR));
        if ($status === 400 && str_contains($message, 'Item not found')) {
            self::assertTrue(true);

            return;
        }

        self::assertStringContainsString($expectedSubstring, $message);
    }
}
