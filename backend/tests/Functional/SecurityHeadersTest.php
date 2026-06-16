<?php

namespace App\Tests\Functional;

class SecurityHeadersTest extends ApiTestCase
{
    public function testOpenApiDocsExposeConfiguredTitle(): void
    {
        $this->client->request('GET', '/api/docs.jsonopenapi');

        self::assertResponseIsSuccessful();
        $data = $this->responseJson();
        self::assertSame('Персональная база знаний API', $data['info']['title'] ?? null);
    }
}
