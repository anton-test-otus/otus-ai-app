<?php

namespace App\Tests\Functional;

use App\Entity\Note;
use App\Entity\User;

class NoteGraphApiTest extends ApiTestCase
{
    private User $user;
    private Note $noteRoot;
    private Note $noteLinked;
    private Note $noteFar;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->userFactory->createUser('graph-api@example.com');
        $this->noteRoot = $this->userFactory->createNote($this->user, 'Root', 'root content');
        $this->noteLinked = $this->userFactory->createNote($this->user, 'Linked', 'linked content');
        $this->noteFar = $this->userFactory->createNote($this->user, 'Far', 'far content');
        $this->token = $this->login($this->user);
    }

    public function testGetNoteGraphDepthTwoReturnsChainNodesAndEdges(): void
    {
        $rootId = (string) $this->noteRoot->getId();
        $linkedId = (string) $this->noteLinked->getId();
        $farId = (string) $this->noteFar->getId();

        // linked→far first: PUT on a link target with orphanRemoval on incomingLinks
        // would otherwise drop the root→linked edge created second.
        $this->jsonRequest('PUT', '/api/notes/' . $linkedId, [
            'title' => 'Linked',
            'content' => 'See [[' . $farId . ']]',
        ], $this->token);
        self::assertResponseIsSuccessful();

        $this->jsonRequest('PUT', '/api/notes/' . $rootId, [
            'title' => 'Root',
            'content' => 'See [[' . $linkedId . ']]',
        ], $this->token);
        self::assertResponseIsSuccessful();

        $this->jsonRequest('GET', '/api/notes/' . $rootId, null, $this->token);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->responseJson()['linkStats']['outgoing']);

        $this->jsonRequest('GET', '/api/notes/' . $linkedId, null, $this->token);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->responseJson()['linkStats']['outgoing']);

        $this->jsonRequest(
            'GET',
            sprintf('/api/notes/%s/graph?depth=2&direction=both', $rootId),
            null,
            $this->token,
        );

        self::assertResponseStatusCodeSame(200);
        $data = $this->responseJson();

        self::assertArrayHasKey('nodes', $data);
        self::assertArrayHasKey('edges', $data);
        self::assertArrayHasKey('truncated', $data);
        self::assertArrayHasKey('frontierNodeIds', $data);

        $nodeIds = array_column($data['nodes'], 'id');
        self::assertContains($rootId, $nodeIds);
        self::assertContains($linkedId, $nodeIds);
        self::assertContains($farId, $nodeIds);

        self::assertTrue($this->hasEdge($data['edges'], $rootId, $linkedId));
        self::assertTrue($this->hasEdge($data['edges'], $linkedId, $farId));
    }

    /**
     * @param list<array{source: string, target: string}> $edges
     */
    private function hasEdge(array $edges, string $source, string $target): bool
    {
        foreach ($edges as $edge) {
            if ($edge['source'] === $source && $edge['target'] === $target) {
                return true;
            }
        }

        return false;
    }
}
