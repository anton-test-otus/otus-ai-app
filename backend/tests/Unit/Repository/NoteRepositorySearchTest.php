<?php

namespace App\Tests\Unit\Repository;

use App\Repository\NoteRepository;
use App\Tests\Factory\UserFactory;
use App\Tests\Functional\ApiTestCase;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\AbstractLogger;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class NoteRepositorySearchTest extends ApiTestCase
{
    private NoteRepository $repository;
    private SqlCaptureLogger $sqlLogger;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->sqlLogger = new SqlCaptureLogger();
        $container->get('doctrine.dbal.default_connection.configuration')
            ->setMiddlewares([new Middleware($this->sqlLogger)]);

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userFactory = new UserFactory(
            $this->entityManager,
            $container->get(UserPasswordHasherInterface::class),
        );
        $this->resetDatabase();

        $this->repository = $this->entityManager->getRepository(\App\Entity\Note::class);
        self::assertInstanceOf(NoteRepository::class, $this->repository);
    }

    public function testSearchWithHelloMatchesCaseInsensitiveContent(): void
    {
        $user = $this->userFactory->createUser('repo-search@example.com');
        $this->userFactory->createNote($user, 'Greeting', 'hello world');

        $result = $this->repository->search($user, $this->searchCriteria(['query' => 'HELLO']), 1, 20);

        self::assertSame(1, $result['total']);
        self::assertCount(1, $result['notes']);
    }

    public function testSearchUsesToTsqueryPrefixOnSearchVector(): void
    {
        $user = $this->userFactory->createUser('repo-search-sql@example.com');
        $this->userFactory->createNote($user, 'Greeting', 'hello world');

        $this->repository->search($user, $this->searchCriteria(['query' => 'HELLO']), 1, 20);

        self::assertNotEmpty($this->sqlLogger->sqlStatements);
        $sql = strtolower(implode("\n", $this->sqlLogger->sqlStatements));
        self::assertStringContainsString('to_tsquery', $sql);
        self::assertStringContainsString('search_vector', $sql);
    }

    public function testSearchPrefixMatchesStartOfWord(): void
    {
        $user = $this->userFactory->createUser('repo-search-prefix@example.com');
        $this->userFactory->createNote($user, 'Факультеты Хогвартса', 'Сравнение факультетов школы.');

        $result = $this->repository->search($user, $this->searchCriteria(['query' => 'факульт']), 1, 20);

        self::assertSame(1, $result['total']);
        self::assertCount(1, $result['notes']);
    }

    public function testSearchIgnoresTokensShorterThanMinPrefixLength(): void
    {
        $user = $this->userFactory->createUser('repo-search-short@example.com');
        $this->userFactory->createNote($user, 'Note', 'hello world');

        $result = $this->repository->search($user, $this->searchCriteria(['query' => 'he']), 1, 20);

        self::assertSame(0, $result['total']);
    }

    public function testSearchMatchesWordInMiddleOfLongContent(): void
    {
        $user = $this->userFactory->createUser('repo-search-middle@example.com');
        $this->userFactory->createNote(
            $user,
            'Длинная заметка',
            'В начале текста много слов, а в середине встречается пророчество и дальше снова текст.',
        );

        $result = $this->repository->search($user, $this->searchCriteria(['query' => 'пророчество']), 1, 20);

        self::assertSame(1, $result['total']);
        self::assertCount(1, $result['notes']);
    }

    public function testSearchCombinesFullTextWithFolderAndTagFilters(): void
    {
        $user = $this->userFactory->createUser('repo-search-filters@example.com');
        $folder = $this->userFactory->createFolder($user, 'Архив');
        $tag = $this->userFactory->createTag($user, 'важное');

        $matching = $this->userFactory->createNote(
            $user,
            'Совпадение',
            'Текст с уникальным словом квиддич внутри.',
            $folder,
        );
        $matching->addTag($tag);
        $this->entityManager->flush();

        $this->userFactory->createNote(
            $user,
            'Другое',
            'Тоже квиддич, но без папки и тега.',
        );

        $result = $this->repository->search(
            $user,
            $this->searchCriteria([
                'query' => 'квиддич',
                'folderId' => $folder->getId(),
                'tags' => [$tag->getId()->toRfc4122()],
            ]),
            1,
            20,
        );

        self::assertSame(1, $result['total']);
        self::assertSame($matching->getId()->toRfc4122(), $result['notes'][0]->getId()->toRfc4122());
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function searchCriteria(array $overrides = []): array
    {
        return array_merge([
            'query' => '',
            'folderId' => null,
            'tags' => [],
            'isFavorite' => null,
            'dateFrom' => null,
            'dateTo' => null,
        ], $overrides);
    }
}

final class SqlCaptureLogger extends AbstractLogger
{
    /** @var list<string> */
    public array $sqlStatements = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        if (isset($context['sql'])) {
            $this->sqlStatements[] = (string) $context['sql'];
        }
    }
}
