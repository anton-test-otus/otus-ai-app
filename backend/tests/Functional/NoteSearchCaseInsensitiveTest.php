<?php

namespace App\Tests\Functional;

use App\Entity\User;

class NoteSearchCaseInsensitiveTest extends ApiTestCase
{
    private User $user;
    private string $token;
    private string $noteId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->userFactory->createUser('search-case@example.com');
        $note = $this->userFactory->createNote(
            $this->user,
            'Hello World',
            'Body with Hello World inside',
        );
        $this->noteId = $note->getId()->toRfc4122();
        $this->token = $this->login($this->user);
    }

    public function testNotesSearchMatchesLowercaseQuery(): void
    {
        $this->jsonRequest('GET', '/api/notes/search?q=hello', null, $this->token);
        self::assertResponseIsSuccessful();
        self::assertSearchContainsNote($this->responseJson(), $this->noteId);
    }

    public function testNotesSearchMatchesUppercaseQuery(): void
    {
        $this->jsonRequest('GET', '/api/notes/search?q=HELLO', null, $this->token);
        self::assertResponseIsSuccessful();
        self::assertSearchContainsNote($this->responseJson(), $this->noteId);
    }

    public function testNotesCollectionTitleFilterIsCaseInsensitive(): void
    {
        $this->jsonRequest('GET', '/api/notes?title=hello', null, $this->token);
        self::assertResponseIsSuccessful();
        self::assertCollectionContainsNote($this->responseJson(), $this->noteId);
    }

    public function testNotesCollectionTitleFilterDoesNotMatchDifferentWord(): void
    {
        $this->jsonRequest('GET', '/api/notes?title=TEST', null, $this->token);
        self::assertResponseIsSuccessful();
        self::assertCollectionDoesNotContainNote($this->responseJson(), $this->noteId);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function assertSearchContainsNote(array $payload, string $noteId): void
    {
        self::assertArrayHasKey('data', $payload);
        self::assertTrue(self::containsNoteId($payload['data'], $noteId));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function assertCollectionContainsNote(array $payload, string $noteId): void
    {
        self::assertTrue(self::containsNoteId(self::collectionMembers($payload), $noteId));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function assertCollectionDoesNotContainNote(array $payload, string $noteId): void
    {
        self::assertFalse(self::containsNoteId(self::collectionMembers($payload), $noteId));
    }

    /**
     * @param list<array<string, mixed>> $notes
     */
    private static function containsNoteId(array $notes, string $noteId): bool
    {
        foreach ($notes as $note) {
            if (($note['id'] ?? null) === $noteId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<array<string, mixed>>
     */
    private static function collectionMembers(array $data): array
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
