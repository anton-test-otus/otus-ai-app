<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;
    protected UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userFactory = new UserFactory(
            $this->entityManager,
            $container->get(UserPasswordHasherInterface::class),
        );
        $this->resetDatabase();
    }

    protected function resetDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DROP SCHEMA IF EXISTS public CASCADE');
        $connection->executeStatement('CREATE SCHEMA public');

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema($metadata);

        $connection->executeStatement(<<<'SQL'
            ALTER TABLE notes ADD COLUMN search_vector tsvector
              GENERATED ALWAYS AS (
                to_tsvector('russian', coalesce(title, '') || ' ' || coalesce(content, ''))
              ) STORED
            SQL);
        $connection->executeStatement('CREATE INDEX notes_search_vector_gin_idx ON notes USING GIN (search_vector)');
    }

    protected function login(User $user, string $password = UserFactory::PASSWORD): string
    {
        $this->jsonRequest('POST', '/api/auth/login', [
            'username' => $user->getEmail(),
            'password' => $password,
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $data['token'];
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, string>     $headers
     */
    protected function jsonRequest(
        string $method,
        string $uri,
        ?array $body = null,
        ?string $token = null,
        array $headers = [],
    ): void {
        $server = array_merge([
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $headers);

        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $this->client->request(
            $method,
            $uri,
            [],
            [],
            $server,
            $body !== null ? json_encode($body, JSON_THROW_ON_ERROR) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function responseJson(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
