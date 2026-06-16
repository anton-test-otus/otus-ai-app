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

    public function testNginxSecurityHeadersAreNotAssertedInPhpUnit(): void
    {
        self::markTestSkipped('Security headers (X-Content-Type-Options, X-Frame-Options, Referrer-Policy) are set by nginx, not the Symfony test client.');
    }
}
