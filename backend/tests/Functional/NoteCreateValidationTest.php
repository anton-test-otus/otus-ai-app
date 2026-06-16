<?php

namespace App\Tests\Functional;

use App\Entity\User;

class NoteCreateValidationTest extends ApiTestCase
{
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->userFactory->createUser('note-create@example.com');
        $this->token = $this->login($this->user);
    }

    public function testPostNoteWithoutContentReturns422(): void
    {
        $this->jsonRequest('POST', '/api/notes', [
            'title' => 'Test',
        ], $this->token);

        self::assertResponseStatusCodeSame(422);
        $data = $this->responseJson();
        $message = (string) ($data['detail'] ?? $data['message'] ?? json_encode($data, JSON_THROW_ON_ERROR));
        self::assertStringContainsString('Содержимое не может быть пустым', $message);
    }

    public function testPostNoteWithEmptyContentReturns422(): void
    {
        $this->jsonRequest('POST', '/api/notes', [
            'title' => 'Test',
            'content' => '',
        ], $this->token);

        self::assertResponseStatusCodeSame(422);
        $data = $this->responseJson();
        $message = (string) ($data['detail'] ?? $data['message'] ?? json_encode($data, JSON_THROW_ON_ERROR));
        self::assertStringContainsString('Содержимое не может быть пустым', $message);
    }

    public function testPostNoteWithValidContentReturns201(): void
    {
        $this->jsonRequest('POST', '/api/notes', [
            'title' => 'Test',
            'content' => 'hello',
        ], $this->token);

        self::assertResponseStatusCodeSame(201);
        $data = $this->responseJson();
        self::assertArrayHasKey('id', $data);
        self::assertSame('Test', $data['title']);
        self::assertSame('hello', $data['content']);
    }
}
