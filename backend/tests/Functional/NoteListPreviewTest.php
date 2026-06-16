<?php

namespace App\Tests\Functional;

use App\Entity\User;

class NoteListPreviewTest extends ApiTestCase
{
    private User $user;
    private string $token;
    private string $targetId;
    private string $sourceId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->userFactory->createUser('preview-list@example.com');
        $target = $this->userFactory->createNote($this->user, 'Target Title', 'Target body');
        $source = $this->userFactory->createNote($this->user, 'Source note', 'plain text');

        $this->targetId = $target->getId()->toRfc4122();
        $this->sourceId = $source->getId()->toRfc4122();
        $this->token = $this->login($this->user);

        $this->jsonRequest('PUT', '/api/notes/' . $this->sourceId, [
            'title' => 'Source note',
            'content' => 'See linked note [[' . $this->targetId . ']]',
        ], $this->token);
        self::assertResponseIsSuccessful();
    }

    public function testNotesCollectionPreviewContainsTargetTitleNotUuid(): void
    {
        $this->jsonRequest('GET', '/api/notes', null, $this->token);
        self::assertResponseIsSuccessful();

        $preview = $this->findNotePreview($this->collectionMembers($this->responseJson()), $this->sourceId);

        self::assertStringContainsString('Target Title', $preview);
        self::assertStringNotContainsString($this->targetId, $preview);
    }

    public function testNotesSearchPreviewContainsTargetTitleNotUuid(): void
    {
        $this->jsonRequest('GET', '/api/notes/search?q=linked', null, $this->token);
        self::assertResponseIsSuccessful();

        $payload = $this->responseJson();
        self::assertArrayHasKey('data', $payload);

        $preview = $this->findNotePreview($payload['data'], $this->sourceId);

        self::assertStringContainsString('Target Title', $preview);
        self::assertStringNotContainsString($this->targetId, $preview);
    }

    /**
     * @param list<array<string, mixed>> $notes
     */
    private function findNotePreview(array $notes, string $noteId): string
    {
        foreach ($notes as $note) {
            if (($note['id'] ?? null) === $noteId) {
                self::assertArrayHasKey('contentPreview', $note);

                return (string) $note['contentPreview'];
            }
        }

        self::fail(sprintf('Note %s was not found in collection response.', $noteId));
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<array<string, mixed>>
     */
    private function collectionMembers(array $data): array
    {
        if (isset($data['hydra:member']) && is_array($data['hydra:member'])) {
            return $data['hydra:member'];
        }

        if (isset($data['member']) && is_array($data['member'])) {
            return $data['member'];
        }

        return array_is_list($data) ? $data : [];
    }
}
