<?php

namespace App\Tests\Functional;

use App\Entity\User;

class UserSettingsValidationTest extends ApiTestCase
{
    private User $userA;
    private string $tokenA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = $this->userFactory->createUser('settings@example.com');
        $this->tokenA = $this->login($this->userA);
    }

    public function testPatchSettingsWithInvalidAutosaveDelayReturns400(): void
    {
        $this->mergePatch('/api/auth/settings', [
            'autosaveDelaySeconds' => 7,
        ], $this->tokenA);

        self::assertResponseStatusCodeSame(400);
        $data = $this->responseJson();
        $errors = $data['errors'] ?? [];
        self::assertArrayHasKey('autosaveDelaySeconds', $errors);
    }

    public function testPatchSettingsWithAllowedAutosaveDelayReturns200(): void
    {
        $this->mergePatch('/api/auth/settings', [
            'autosaveDelaySeconds' => 5,
        ], $this->tokenA);

        self::assertResponseStatusCodeSame(200);
        $data = $this->responseJson();
        self::assertArrayHasKey('settings', $data);
        self::assertSame(5, $data['settings']['autosaveDelaySeconds']);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function mergePatch(string $uri, array $body, string $token): void
    {
        $this->jsonRequest('PATCH', $uri, $body, $token, [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ]);
    }
}
