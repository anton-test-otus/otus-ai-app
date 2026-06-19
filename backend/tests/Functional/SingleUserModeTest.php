<?php

namespace App\Tests\Functional;

use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SingleUserModeTest extends WebTestCase
{
    private const SINGLE_USER_EMAIL = 'owner@local';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        parent::setUp();

        $this->client = static::createClient(['environment' => 'test_single_user']);
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->resetDatabase();
        $this->createSingleUser();
    }

    public function testMeWorksWithoutAuthorizationHeader(): void
    {
        $this->jsonRequest('GET', '/api/auth/me');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(self::SINGLE_USER_EMAIL, $data['email']);
    }

    public function testNotesCollectionWorksWithoutAuthorizationHeader(): void
    {
        $this->jsonRequest('GET', '/api/notes');

        self::assertResponseIsSuccessful();
    }

    public function testLoginIsDisabled(): void
    {
        $this->jsonRequest('POST', '/api/auth/login', [
            'username' => self::SINGLE_USER_EMAIL,
            'password' => 'any-password',
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testRegisterIsDisabled(): void
    {
        $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'new@example.com',
            'password' => UserFactory::PASSWORD,
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    private function resetDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DROP SCHEMA IF EXISTS public CASCADE');
        $connection->executeStatement('CREATE SCHEMA public');

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema($metadata);
    }

    private function createSingleUser(): void
    {
        $factory = new UserFactory(
            $this->entityManager,
            static::getContainer()->get(UserPasswordHasherInterface::class),
        );
        $factory->createUser(self::SINGLE_USER_EMAIL);
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function jsonRequest(string $method, string $uri, ?array $body = null): void
    {
        $this->client->request(
            $method,
            $uri,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            $body !== null ? json_encode($body, JSON_THROW_ON_ERROR) : null,
        );
    }
}
