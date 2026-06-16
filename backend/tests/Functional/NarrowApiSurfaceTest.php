<?php

namespace App\Tests\Functional;

use App\Entity\Note;
use App\Entity\User;

class NarrowApiSurfaceTest extends ApiTestCase
{
    private User $userA;
    private Note $noteA;
    private Note $noteTarget;
    private string $tokenA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = $this->userFactory->createUser('narrow-api@example.com');
        $this->noteA = $this->userFactory->createNote($this->userA, 'Note A', 'initial content');
        $this->noteTarget = $this->userFactory->createNote($this->userA, 'Note Target', 'target content');
        $this->tokenA = $this->login($this->userA);

        $this->noteA->setUpdatedAt(new \DateTimeImmutable('-10 minutes'));
        $this->entityManager->flush();

        $this->jsonRequest('PUT', '/api/notes/' . $this->noteA->getId(), [
            'title' => 'Note A',
            'content' => 'versioned content',
        ], $this->tokenA);
        self::assertResponseIsSuccessful();
    }

    public function testGetNoteLinksCollectionReturns404(): void
    {
        $this->jsonRequest('GET', '/api/note_links', null, $this->tokenA);

        self::assertResponseStatusCodeSame(404);
    }

    public function testPostNoteLinksReturns404(): void
    {
        $this->jsonRequest('POST', '/api/note_links', [
            'sourceNote' => '/api/notes/' . $this->noteA->getId(),
            'targetNote' => '/api/notes/' . $this->noteTarget->getId(),
        ], $this->tokenA);

        self::assertResponseStatusCodeSame(404);
    }

    public function testGetGlobalNoteVersionsReturns404(): void
    {
        $this->jsonRequest('GET', '/api/note_versions', null, $this->tokenA);

        self::assertResponseStatusCodeSame(404);
    }

    public function testGetNoteBacklinksReturns404(): void
    {
        $this->jsonRequest('GET', '/api/notes/' . $this->noteA->getId() . '/backlinks', null, $this->tokenA);

        self::assertResponseStatusCodeSame(404);
    }

    public function testGetNoteVersionsReturns200(): void
    {
        $this->jsonRequest('GET', '/api/notes/' . $this->noteA->getId() . '/versions', null, $this->tokenA);

        self::assertResponseStatusCodeSame(200);
        $members = $this->collectionMembers($this->responseJson());
        self::assertGreaterThanOrEqual(1, count($members));
    }

    public function testGetNoteGraphReturns200WithNodesAndEdges(): void
    {
        $this->jsonRequest('GET', '/api/notes/' . $this->noteA->getId() . '/graph', null, $this->tokenA);

        self::assertResponseStatusCodeSame(200);
        $data = $this->responseJson();
        self::assertArrayHasKey('nodes', $data);
        self::assertArrayHasKey('edges', $data);
        self::assertIsArray($data['nodes']);
        self::assertIsArray($data['edges']);
    }

    public function testPutNoteWithWikiLinkUpdatesLinkStats(): void
    {
        $targetId = (string) $this->noteTarget->getId();

        $this->jsonRequest('PUT', '/api/notes/' . $this->noteA->getId(), [
            'title' => 'Note A',
            'content' => 'See [[' . $targetId . ']]',
        ], $this->tokenA);
        self::assertResponseStatusCodeSame(200);

        $this->jsonRequest('GET', '/api/notes/' . $this->noteA->getId(), null, $this->tokenA);
        self::assertResponseStatusCodeSame(200);
        $data = $this->responseJson();

        self::assertArrayHasKey('linkStats', $data);
        self::assertGreaterThanOrEqual(1, $data['linkStats']['outgoing']);
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
