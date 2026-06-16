<?php

namespace App\Tests\Unit\Repository;

use App\Repository\UserRepository;
use App\Tests\Functional\ApiTestCase;

class UserRepositoryStatisticsTest extends ApiTestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->entityManager->getRepository(\App\Entity\User::class);
        self::assertInstanceOf(UserRepository::class, $this->repository);
    }

    public function testGetUsersStatisticsBatchReturnsEmptyArrayForEmptyInput(): void
    {
        self::assertSame([], $this->repository->getUsersStatisticsBatch([]));
    }

    public function testGetUsersStatisticsBatchReturnsZerosForUserWithoutData(): void
    {
        $user = $this->userFactory->createUser('stats-empty@example.com');
        $userId = $user->getId()->toRfc4122();

        $stats = $this->repository->getUsersStatisticsBatch([$userId]);

        self::assertSame([
            $userId => [
                'notesCount' => 0,
                'foldersCount' => 0,
                'tagsCount' => 0,
                'lastActivity' => null,
                'storageSize' => 0,
            ],
        ], $stats);
    }

    public function testGetUsersStatisticsBatchAggregatesSingleUserData(): void
    {
        $user = $this->userFactory->createUser('stats-single@example.com');
        $folder = $this->userFactory->createFolder($user, 'Folder');
        $this->userFactory->createTag($user, 'tag-a');
        $note = $this->userFactory->createNote($user, 'Note', 'hello content', $folder);
        $this->userFactory->softDeleteNote(
            $this->userFactory->createNote($user, 'Deleted', 'deleted content should not count'),
        );

        $userId = $user->getId()->toRfc4122();
        $stats = $this->repository->getUsersStatisticsBatch([$userId])[$userId];

        self::assertSame(1, $stats['notesCount']);
        self::assertSame(1, $stats['foldersCount']);
        self::assertSame(1, $stats['tagsCount']);
        self::assertSame(strlen('hello content'), $stats['storageSize']);
        self::assertInstanceOf(\DateTimeImmutable::class, $stats['lastActivity']);
        self::assertSame(
            $note->getUpdatedAt()?->format('Y-m-d H:i:s'),
            $stats['lastActivity']->format('Y-m-d H:i:s'),
        );
    }

    public function testGetUsersStatisticsBatchReturnsStatsForMultipleUsers(): void
    {
        $userA = $this->userFactory->createUser('stats-a@example.com');
        $userB = $this->userFactory->createUser('stats-b@example.com');

        $this->userFactory->createNote($userA, 'A1', 'aaa');
        $this->userFactory->createNote($userA, 'A2', 'bbb');
        $this->userFactory->createFolder($userA, 'Folder A');
        $this->userFactory->createTag($userA, 'tag-a');

        $this->userFactory->createNote($userB, 'B1', 'ccc');

        $stats = $this->repository->getUsersStatisticsBatch([
            $userA->getId()->toRfc4122(),
            $userB->getId()->toRfc4122(),
        ]);

        self::assertSame(2, $stats[$userA->getId()->toRfc4122()]['notesCount']);
        self::assertSame(1, $stats[$userA->getId()->toRfc4122()]['foldersCount']);
        self::assertSame(1, $stats[$userA->getId()->toRfc4122()]['tagsCount']);
        self::assertSame(strlen('aaa') + strlen('bbb'), $stats[$userA->getId()->toRfc4122()]['storageSize']);

        self::assertSame(1, $stats[$userB->getId()->toRfc4122()]['notesCount']);
        self::assertSame(0, $stats[$userB->getId()->toRfc4122()]['foldersCount']);
        self::assertSame(0, $stats[$userB->getId()->toRfc4122()]['tagsCount']);
        self::assertSame(strlen('ccc'), $stats[$userB->getId()->toRfc4122()]['storageSize']);
    }
}
