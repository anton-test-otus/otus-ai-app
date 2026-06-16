<?php

namespace App\Tests\Functional;

use App\Tests\Factory\UserFactory;

class JwtRefreshTest extends ApiTestCase
{
    public function testLoginReturnsTokenRefreshTokenAndUser(): void
    {
        $user = $this->userFactory->createUser('jwt-refresh@example.com');

        $this->jsonRequest('POST', '/api/auth/login', [
            'username' => $user->getEmail(),
            'password' => UserFactory::PASSWORD,
        ]);

        self::assertResponseIsSuccessful();
        $data = $this->responseJson();

        self::assertArrayHasKey('token', $data);
        self::assertNotEmpty($data['token']);
        self::assertArrayHasKey('refreshToken', $data);
        self::assertNotEmpty($data['refreshToken']);
        self::assertArrayHasKey('user', $data);
        self::assertSame($user->getEmail(), $data['user']['email']);
    }

    public function testRefreshRotatesTokensAndOldRefreshTokenIsRejected(): void
    {
        $user = $this->userFactory->createUser('jwt-rotate@example.com');

        $this->jsonRequest('POST', '/api/auth/login', [
            'username' => $user->getEmail(),
            'password' => UserFactory::PASSWORD,
        ]);
        self::assertResponseIsSuccessful();
        $login = $this->responseJson();

        $oldRefreshToken = $login['refreshToken'];

        $this->jsonRequest('POST', '/api/auth/refresh', [
            'refreshToken' => $oldRefreshToken,
        ]);
        self::assertResponseIsSuccessful();
        $refresh = $this->responseJson();

        self::assertArrayHasKey('token', $refresh);
        self::assertNotEmpty($refresh['token']);
        self::assertArrayHasKey('refreshToken', $refresh);
        self::assertNotEmpty($refresh['refreshToken']);
        self::assertNotSame($oldRefreshToken, $refresh['refreshToken']);

        $this->jsonRequest('POST', '/api/auth/refresh', [
            'refreshToken' => $oldRefreshToken,
        ]);
        self::assertResponseStatusCodeSame(401);

        $this->jsonRequest('GET', '/api/auth/me', null, $refresh['token']);
        self::assertResponseIsSuccessful();
        $me = $this->responseJson();
        self::assertSame($user->getEmail(), $me['email']);
    }

    public function testRefreshWithInvalidTokenReturns401(): void
    {
        $this->jsonRequest('POST', '/api/auth/refresh', [
            'refreshToken' => 'not-a-valid-refresh-token',
        ]);

        self::assertResponseStatusCodeSame(401);
    }
}
