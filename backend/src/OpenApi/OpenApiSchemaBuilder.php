<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use Symfony\Component\HttpFoundation\Response;

final class OpenApiSchemaBuilder
{
    public static function uuidPathParam(string $name, string $description): Parameter
    {
        return new Parameter($name, 'path', $description, true, false, false, [
            'type' => 'string',
            'format' => 'uuid',
        ]);
    }

    public static function queryIntParam(string $name, string $description, mixed $default = null): Parameter
    {
        $schema = ['type' => 'integer'];
        if ($default !== null) {
            $schema['default'] = $default;
        }

        return new Parameter($name, 'query', $description, false, false, false, $schema);
    }

    public static function queryStringParam(string $name, string $description, mixed $default = null): Parameter
    {
        $schema = ['type' => 'string'];
        if ($default !== null) {
            $schema['default'] = $default;
        }

        return new Parameter($name, 'query', $description, false, false, false, $schema);
    }

    public static function jwtSecurity(): array
    {
        return [['JWT' => []]];
    }

    /**
     * @param array<string, mixed> $properties
     * @param list<string>         $required
     */
    public static function objectSchema(array $properties, array $required = []): array
    {
        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $schema
     */
    public static function jsonRequestBody(array $schema, string $description = 'Request body'): RequestBody
    {
        return (new RequestBody())
            ->withDescription($description)
            ->withRequired(true)
            ->withContent(new \ArrayObject([
                'application/json' => new MediaType(new \ArrayObject($schema)),
            ]));
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<int|string, array<string, mixed>>
     */
    public static function jsonResponse(int $status, array $schema, string $description): array
    {
        return [
            $status => [
                'description' => $description,
                'content' => [
                    'application/json' => [
                        'schema' => $schema,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public static function errorResponses(): array
    {
        return [
            Response::HTTP_BAD_REQUEST => [
                'description' => 'Некорректный запрос',
            ],
            Response::HTTP_UNAUTHORIZED => [
                'description' => 'Требуется авторизация',
            ],
            Response::HTTP_FORBIDDEN => [
                'description' => 'Доступ запрещён',
            ],
        ];
    }

    public static function userProperties(): array
    {
        return [
            'id' => ['type' => 'string', 'format' => 'uuid'],
            'email' => ['type' => 'string', 'format' => 'email'],
            'roles' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
            'isActive' => ['type' => 'boolean'],
            'createdAt' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
            'settings' => self::objectSchema([
                'autosaveDelaySeconds' => ['type' => 'integer'],
                'versionConsolidationWindowMinutes' => ['type' => 'integer'],
            ]),
            'defaults' => self::objectSchema([
                'autosaveDelaySeconds' => ['type' => 'integer'],
                'versionConsolidationWindowMinutes' => ['type' => 'integer'],
            ]),
        ];
    }

    public static function authTokenResponseSchema(): array
    {
        return self::objectSchema([
            'token' => ['type' => 'string'],
            'refreshToken' => ['type' => 'string'],
            'user' => self::objectSchema(self::userProperties(), ['id', 'email', 'roles', 'isActive', 'settings', 'defaults']),
        ], ['token', 'refreshToken', 'user']);
    }

    public static function adminUserStatisticsSchema(): array
    {
        return self::objectSchema([
            'notesCount' => ['type' => 'integer'],
            'foldersCount' => ['type' => 'integer'],
            'tagsCount' => ['type' => 'integer'],
            'lastActivity' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
            'storageSize' => ['type' => 'integer'],
        ]);
    }

    public static function adminUserSchema(): array
    {
        return self::objectSchema([
            'id' => ['type' => 'string', 'format' => 'uuid'],
            'email' => ['type' => 'string', 'format' => 'email'],
            'roles' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
            'isActive' => ['type' => 'boolean'],
            'createdAt' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
            'statistics' => self::adminUserStatisticsSchema(),
        ], ['id', 'email', 'roles', 'isActive', 'statistics']);
    }

    public static function operation(
        string $operationId,
        array $tags,
        string $summary,
        array $responses,
        ?RequestBody $requestBody = null,
        ?array $parameters = null,
        ?array $security = null,
        ?string $description = null,
    ): Operation {
        $operation = (new Operation())
            ->withOperationId($operationId)
            ->withTags($tags)
            ->withSummary($summary)
            ->withResponses($responses);

        if ($description !== null) {
            $operation = $operation->withDescription($description);
        }

        if ($requestBody !== null) {
            $operation = $operation->withRequestBody($requestBody);
        }

        if ($parameters !== null) {
            $operation = $operation->withParameters($parameters);
        }

        if ($security !== null) {
            $operation = $operation->withSecurity($security);
        }

        return $operation;
    }
}
