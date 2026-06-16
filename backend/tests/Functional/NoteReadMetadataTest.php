<?php

namespace App\Tests\Functional;

use App\Entity\User;

class NoteReadMetadataTest extends ApiTestCase
{
    public function testGetNoteIncludesLinkStatsAndVersionCount(): void
    {
        $user = $this->userFactory->createUser('read-metadata@example.com');
        $target = $this->userFactory->createNote($user, 'Target', 'target content');
        $source = $this->userFactory->createNote($user, 'Source', 'plain content');

        $token = $this->login($user);

        $this->jsonRequest('PUT', '/api/notes/' . $source->getId(), [
            'title' => 'Source',
            'content' => 'Link [[' . $target->getId() . ']]',
        ], $token);
        self::assertResponseIsSuccessful();

        $this->entityManager->clear();
        /** @var \App\Entity\Note $managedSource */
        $managedSource = $this->entityManager->find(\App\Entity\Note::class, $source->getId());
        $this->userFactory->createNoteVersion($managedSource, 'older content');
        $this->userFactory->createNoteVersion($managedSource, 'newer content');

        $this->jsonRequest('GET', '/api/notes/' . $source->getId(), null, $token);
        self::assertResponseIsSuccessful();

        $data = $this->responseJson();

        self::assertArrayHasKey('linkStats', $data);
        self::assertSame(['incoming' => 0, 'outgoing' => 1], $data['linkStats']);
        self::assertArrayHasKey('versionCount', $data);
        self::assertSame(2, $data['versionCount']);
    }
}
