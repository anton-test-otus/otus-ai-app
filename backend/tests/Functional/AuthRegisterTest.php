<?php

namespace App\Tests\Functional;

class AuthRegisterTest extends ApiTestCase
{
    public function testRegisterNewEmailReturns201WithTokenAndUser(): void
    {
        $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'new-user@example.com',
            'password' => 'secret12',
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $this->responseJson();

        self::assertArrayHasKey('token', $data);
        self::assertNotEmpty($data['token']);
        self::assertArrayHasKey('user', $data);
        self::assertSame('new-user@example.com', $data['user']['email']);
    }

    public function testRegisterDuplicateEmailReturns409(): void
    {
        $this->userFactory->createUser('duplicate@example.com');

        $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'duplicate@example.com',
            'password' => 'secret12',
        ]);

        self::assertResponseStatusCodeSame(409);
        $data = $this->responseJson();
        self::assertSame(['error' => 'Email уже занят'], $data);
    }

    public function testRegisterInvalidEmailReturns400WithErrors(): void
    {
        $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'not-an-email',
            'password' => 'secret12',
        ]);

        self::assertResponseStatusCodeSame(400);
        $data = $this->responseJson();

        self::assertArrayHasKey('errors', $data);
        self::assertArrayHasKey('email', $data['errors']);
        self::assertNotSame(409, $this->client->getResponse()->getStatusCode());
    }
}
