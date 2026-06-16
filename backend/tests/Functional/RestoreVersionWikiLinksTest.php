<?php

namespace App\Tests\Functional;

use App\Entity\Note;
use App\Entity\User;

class RestoreVersionWikiLinksTest extends ApiTestCase
{
    /**
     * @return array{
     *     user: User,
     *     token: string,
     *     noteTarget: Note,
     *     noteA: Note,
     *     versionWithoutLinks: string,
     *     versionWithLinks: string,
     *     linkContent: string
     * }
     */
    private function createWikiLinkFixture(): array
    {
        $user = $this->userFactory->createUser('user-a@example.com');
        $noteTarget = $this->userFactory->createNote($user, 'Target note', 'Target content');
        $noteA = $this->userFactory->createNote($user, 'Note A', 'Initial content');

        $targetId = $noteTarget->getId()->toRfc4122();
        $linkContent = sprintf('Link to target [[%s]]', $targetId);

        $token = $this->login($user);

        $this->jsonRequest('PUT', '/api/notes/' . $noteA->getId()->toRfc4122(), [
            'title' => 'Note A',
            'content' => $linkContent,
        ], $token);
        self::assertResponseIsSuccessful();

        $noteAfterPut = $this->fetchNote($noteA->getId()->toRfc4122(), $token);
        self::assertGreaterThanOrEqual(1, $noteAfterPut['linkStats']['outgoing']);

        $this->entityManager->clear();
        /** @var Note $managedNoteA */
        $managedNoteA = $this->entityManager->find(Note::class, $noteA->getId());

        $versionWithoutLinks = $this->userFactory
            ->createNoteVersion($managedNoteA, 'Plain text without wiki links')
            ->getId()
            ->toRfc4122();
        $versionWithLinks = $this->userFactory
            ->createNoteVersion($managedNoteA, $linkContent)
            ->getId()
            ->toRfc4122();

        return [
            'user' => $user,
            'token' => $token,
            'noteTarget' => $noteTarget,
            'noteA' => $noteA,
            'versionWithoutLinks' => $versionWithoutLinks,
            'versionWithLinks' => $versionWithLinks,
            'linkContent' => $linkContent,
        ];
    }

    public function testRestoreVersionWithoutLinksOverwriteClearsOutgoingLinks(): void
    {
        $fixture = $this->createWikiLinkFixture();
        $noteId = $fixture['noteA']->getId()->toRfc4122();

        $this->jsonRequest(
            'POST',
            sprintf('/api/notes/%s/versions/%s/restore', $noteId, $fixture['versionWithoutLinks']),
            ['mode' => 'overwrite'],
            $fixture['token'],
        );
        self::assertResponseIsSuccessful();

        $note = $this->fetchNote($noteId, $fixture['token']);
        self::assertSame(0, $note['linkStats']['outgoing']);
        self::assertStringNotContainsString('[[', $note['content']);
    }

    public function testRestoreVersionWithLinksOverwriteRestoresOutgoingLinks(): void
    {
        $fixture = $this->createWikiLinkFixture();
        $noteId = $fixture['noteA']->getId()->toRfc4122();

        $this->restoreVersion($noteId, $fixture['versionWithoutLinks'], 'overwrite', $fixture['token']);

        $this->jsonRequest(
            'POST',
            sprintf('/api/notes/%s/versions/%s/restore', $noteId, $fixture['versionWithLinks']),
            ['mode' => 'overwrite'],
            $fixture['token'],
        );
        self::assertResponseIsSuccessful();

        $note = $this->fetchNote($noteId, $fixture['token']);
        self::assertGreaterThanOrEqual(1, $note['linkStats']['outgoing']);
        self::assertSame($fixture['linkContent'], $note['content']);
    }

    public function testRestoreCreateVersionModeSyncsLinks(): void
    {
        $fixture = $this->createWikiLinkFixture();
        $noteId = $fixture['noteA']->getId()->toRfc4122();

        $this->restoreVersion($noteId, $fixture['versionWithoutLinks'], 'overwrite', $fixture['token']);

        $this->jsonRequest(
            'POST',
            sprintf('/api/notes/%s/versions/%s/restore', $noteId, $fixture['versionWithLinks']),
            ['mode' => 'create_version'],
            $fixture['token'],
        );
        self::assertResponseIsSuccessful();

        $note = $this->fetchNote($noteId, $fixture['token']);
        self::assertGreaterThanOrEqual(1, $note['linkStats']['outgoing']);
        self::assertSame($fixture['linkContent'], $note['content']);
    }

    public function testRestoreCopyModeCreatesNewNoteWithLinks(): void
    {
        $fixture = $this->createWikiLinkFixture();
        $noteId = $fixture['noteA']->getId()->toRfc4122();

        $this->jsonRequest(
            'POST',
            sprintf('/api/notes/%s/versions/%s/restore', $noteId, $fixture['versionWithLinks']),
            ['mode' => 'copy'],
            $fixture['token'],
        );
        self::assertResponseIsSuccessful();

        $copiedNote = $this->responseJson();
        self::assertArrayHasKey('id', $copiedNote);
        self::assertNotSame($noteId, $copiedNote['id']);
        self::assertGreaterThanOrEqual(1, $copiedNote['linkStats']['outgoing']);
        self::assertStringContainsString($fixture['noteTarget']->getId()->toRfc4122(), $copiedNote['content']);
    }

    private function restoreVersion(string $noteId, string $versionId, string $mode, string $token): void
    {
        $this->jsonRequest(
            'POST',
            sprintf('/api/notes/%s/versions/%s/restore', $noteId, $versionId),
            ['mode' => $mode],
            $token,
        );
        self::assertResponseIsSuccessful();
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchNote(string $noteId, string $token): array
    {
        $this->jsonRequest('GET', '/api/notes/' . $noteId, null, $token);
        self::assertResponseIsSuccessful();

        return $this->responseJson();
    }
}
