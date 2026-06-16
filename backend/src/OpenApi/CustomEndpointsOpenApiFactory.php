<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\HttpFoundation\Response;

#[AsDecorator(decorates: 'lexik_jwt_authentication.api_platform.openapi.factory')]
final class CustomEndpointsOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();

        $this->enhanceLoginPath($paths);
        $this->addAuthPaths($paths);
        $this->addAdminPaths($paths);
        $this->addWikiLinkPaths($paths);

        return $openApi;
    }

    private function enhanceLoginPath(Paths $paths): void
    {
        $pathItem = $paths->getPath('/api/auth/login');
        if ($pathItem === null || $pathItem->getPost() === null) {
            return;
        }

        $post = $pathItem->getPost()
            ->withTags(['Auth'])
            ->withSummary('Вход в систему')
            ->withDescription('Возвращает access token, refresh token и профиль пользователя.')
            ->withResponses([
                Response::HTTP_OK => [
                    'description' => 'Успешная аутентификация',
                    'content' => [
                        'application/json' => [
                            'schema' => OpenApiSchemaBuilder::authTokenResponseSchema(),
                        ],
                    ],
                ],
            ]);

        $paths->addPath('/api/auth/login', $pathItem->withPost($post));
    }

    private function addAuthPaths(Paths $paths): void
    {
        $paths->addPath('/api/auth/register', (new PathItem())->withPost(
            OpenApiSchemaBuilder::operation(
                operationId: 'auth_register_post',
                tags: ['Auth'],
                summary: 'Регистрация пользователя',
                description: 'Создаёт учётную запись. Первый пользователь получает ROLE_ADMIN.',
                responses: OpenApiSchemaBuilder::jsonResponse(
                    Response::HTTP_CREATED,
                    OpenApiSchemaBuilder::authTokenResponseSchema(),
                    'Пользователь создан',
                ) + [
                    Response::HTTP_BAD_REQUEST => ['description' => 'Ошибка валидации'],
                    Response::HTTP_CONFLICT => ['description' => 'Email уже занят'],
                ],
                requestBody: OpenApiSchemaBuilder::jsonRequestBody(
                    OpenApiSchemaBuilder::objectSchema([
                        'email' => ['type' => 'string', 'format' => 'email'],
                        'password' => ['type' => 'string', 'minLength' => 6],
                    ], ['email', 'password']),
                    'Email и пароль',
                ),
            ),
        ));

        $paths->addPath('/api/auth/refresh', (new PathItem())->withPost(
            OpenApiSchemaBuilder::operation(
                operationId: 'auth_refresh_post',
                tags: ['Auth'],
                summary: 'Обновление access token',
                description: 'По refresh token выдаёт новую пару access/refresh. Старый refresh token инвалидируется.',
                responses: OpenApiSchemaBuilder::jsonResponse(
                    Response::HTTP_OK,
                    OpenApiSchemaBuilder::objectSchema([
                        'token' => ['type' => 'string'],
                        'refreshToken' => ['type' => 'string'],
                    ], ['token', 'refreshToken']),
                    'Токены обновлены',
                ) + [
                    Response::HTTP_UNAUTHORIZED => ['description' => 'Невалидный или просроченный refresh token'],
                ],
                requestBody: OpenApiSchemaBuilder::jsonRequestBody(
                    OpenApiSchemaBuilder::objectSchema([
                        'refreshToken' => ['type' => 'string'],
                    ], ['refreshToken']),
                ),
            ),
        ));

        $paths->addPath('/api/auth/me', (new PathItem())->withGet(
            OpenApiSchemaBuilder::operation(
                operationId: 'auth_me_get',
                tags: ['Auth'],
                summary: 'Текущий пользователь',
                responses: OpenApiSchemaBuilder::jsonResponse(
                    Response::HTTP_OK,
                    OpenApiSchemaBuilder::objectSchema(OpenApiSchemaBuilder::userProperties()),
                    'Профиль пользователя',
                ) + OpenApiSchemaBuilder::errorResponses(),
                security: OpenApiSchemaBuilder::jwtSecurity(),
            ),
        ));

        $paths->addPath('/api/auth/settings', (new PathItem())->withPatch(
            OpenApiSchemaBuilder::operation(
                operationId: 'auth_settings_patch',
                tags: ['Auth'],
                summary: 'Обновление настроек пользователя',
                responses: OpenApiSchemaBuilder::jsonResponse(
                    Response::HTTP_OK,
                    OpenApiSchemaBuilder::objectSchema(OpenApiSchemaBuilder::userProperties()),
                    'Обновлённый профиль',
                ) + OpenApiSchemaBuilder::errorResponses(),
                requestBody: OpenApiSchemaBuilder::jsonRequestBody(
                    OpenApiSchemaBuilder::objectSchema([
                        'autosaveDelaySeconds' => ['type' => 'integer'],
                        'versionConsolidationWindowMinutes' => ['type' => 'integer'],
                    ]),
                    'Настройки для обновления (передавайте только изменяемые поля)',
                ),
                security: OpenApiSchemaBuilder::jwtSecurity(),
            ),
        ));

        $paths->addPath('/api/auth/change-password', (new PathItem())->withPost(
            OpenApiSchemaBuilder::operation(
                operationId: 'auth_change_password_post',
                tags: ['Auth'],
                summary: 'Смена пароля',
                responses: OpenApiSchemaBuilder::jsonResponse(
                    Response::HTTP_OK,
                    OpenApiSchemaBuilder::objectSchema([
                        'message' => ['type' => 'string'],
                    ], ['message']),
                    'Пароль изменён',
                ) + OpenApiSchemaBuilder::errorResponses(),
                requestBody: OpenApiSchemaBuilder::jsonRequestBody(
                    OpenApiSchemaBuilder::objectSchema([
                        'currentPassword' => ['type' => 'string'],
                        'newPassword' => ['type' => 'string', 'minLength' => 6],
                    ], ['currentPassword', 'newPassword']),
                ),
                security: OpenApiSchemaBuilder::jwtSecurity(),
            ),
        ));
    }

    private function addAdminPaths(Paths $paths): void
    {
        $userIdParam = OpenApiSchemaBuilder::uuidPathParam('id', 'UUID пользователя');

        $paths->addPath('/api/admin/users', (new PathItem())->withGet(
            OpenApiSchemaBuilder::operation(
                operationId: 'admin_users_list',
                tags: ['Admin'],
                summary: 'Список пользователей',
                responses: OpenApiSchemaBuilder::jsonResponse(
                    Response::HTTP_OK,
                    OpenApiSchemaBuilder::objectSchema([
                        'data' => [
                            'type' => 'array',
                            'items' => OpenApiSchemaBuilder::adminUserSchema(),
                        ],
                        'meta' => OpenApiSchemaBuilder::objectSchema([
                            'currentPage' => ['type' => 'integer'],
                            'perPage' => ['type' => 'integer'],
                            'total' => ['type' => 'integer'],
                            'totalPages' => ['type' => 'integer'],
                        ], ['currentPage', 'perPage', 'total', 'totalPages']),
                    ], ['data', 'meta']),
                    'Список пользователей с пагинацией',
                ) + OpenApiSchemaBuilder::errorResponses(),
                parameters: [
                    OpenApiSchemaBuilder::queryIntParam('page', 'Номер страницы', 1),
                    OpenApiSchemaBuilder::queryIntParam('perPage', 'Размер страницы (1–100)', 20),
                    OpenApiSchemaBuilder::queryStringParam('q', 'Поиск по email'),
                ],
                security: OpenApiSchemaBuilder::jwtSecurity(),
            ),
        ));

        $paths->addPath('/api/admin/users/{id}', (new PathItem())
            ->withGet(
                OpenApiSchemaBuilder::operation(
                    operationId: 'admin_users_get',
                    tags: ['Admin'],
                    summary: 'Данные пользователя',
                    responses: OpenApiSchemaBuilder::jsonResponse(
                        Response::HTTP_OK,
                        OpenApiSchemaBuilder::adminUserSchema(),
                        'Профиль пользователя',
                    ) + OpenApiSchemaBuilder::errorResponses(),
                    parameters: [$userIdParam],
                    security: OpenApiSchemaBuilder::jwtSecurity(),
                ),
            )
            ->withDelete(
                OpenApiSchemaBuilder::operation(
                    operationId: 'admin_users_delete',
                    tags: ['Admin'],
                    summary: 'Удаление пользователя',
                    description: 'Удаляет пользователя и все его данные. Нельзя удалить собственную учётную запись.',
                    responses: OpenApiSchemaBuilder::jsonResponse(
                        Response::HTTP_OK,
                        OpenApiSchemaBuilder::objectSchema([
                            'message' => ['type' => 'string'],
                        ], ['message']),
                        'Пользователь удалён',
                    ) + OpenApiSchemaBuilder::errorResponses(),
                    parameters: [$userIdParam],
                    security: OpenApiSchemaBuilder::jwtSecurity(),
                ),
            ));

        $paths->addPath('/api/admin/users/{id}/enable', (new PathItem())->withPatch(
            OpenApiSchemaBuilder::operation(
                operationId: 'admin_users_enable',
                tags: ['Admin'],
                summary: 'Активация пользователя',
                responses: OpenApiSchemaBuilder::jsonResponse(
                    Response::HTTP_OK,
                    OpenApiSchemaBuilder::objectSchema([
                        'message' => ['type' => 'string'],
                        'user' => OpenApiSchemaBuilder::objectSchema([
                            'id' => ['type' => 'string', 'format' => 'uuid'],
                            'isActive' => ['type' => 'boolean'],
                        ], ['id', 'isActive']),
                    ], ['message', 'user']),
                    'Пользователь активирован',
                ) + OpenApiSchemaBuilder::errorResponses(),
                parameters: [$userIdParam],
                security: OpenApiSchemaBuilder::jwtSecurity(),
            ),
        ));

        $paths->addPath('/api/admin/users/{id}/disable', (new PathItem())->withPatch(
            OpenApiSchemaBuilder::operation(
                operationId: 'admin_users_disable',
                tags: ['Admin'],
                summary: 'Деактивация пользователя',
                description: 'Нельзя деактивировать собственную учётную запись.',
                responses: OpenApiSchemaBuilder::jsonResponse(
                    Response::HTTP_OK,
                    OpenApiSchemaBuilder::objectSchema([
                        'message' => ['type' => 'string'],
                        'user' => OpenApiSchemaBuilder::objectSchema([
                            'id' => ['type' => 'string', 'format' => 'uuid'],
                            'isActive' => ['type' => 'boolean'],
                        ], ['id', 'isActive']),
                    ], ['message', 'user']),
                    'Пользователь деактивирован',
                ) + OpenApiSchemaBuilder::errorResponses(),
                parameters: [$userIdParam],
                security: OpenApiSchemaBuilder::jwtSecurity(),
            ),
        ));

        $paths->addPath('/api/admin/users/{id}/promote', (new PathItem())->withPatch(
            OpenApiSchemaBuilder::operation(
                operationId: 'admin_users_promote',
                tags: ['Admin'],
                summary: 'Назначение администратором',
                responses: OpenApiSchemaBuilder::jsonResponse(
                    Response::HTTP_OK,
                    OpenApiSchemaBuilder::objectSchema([
                        'message' => ['type' => 'string'],
                        'user' => OpenApiSchemaBuilder::objectSchema([
                            'id' => ['type' => 'string', 'format' => 'uuid'],
                            'email' => ['type' => 'string', 'format' => 'email'],
                            'roles' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ]),
                    ], ['message']),
                    'Роль назначена или уже была',
                ) + OpenApiSchemaBuilder::errorResponses(),
                parameters: [$userIdParam],
                security: OpenApiSchemaBuilder::jwtSecurity(),
            ),
        ));

        $paths->addPath('/api/admin/users/{id}/demote', (new PathItem())->withPatch(
            OpenApiSchemaBuilder::operation(
                operationId: 'admin_users_demote',
                tags: ['Admin'],
                summary: 'Снятие роли администратора',
                description: 'Нельзя снять роль у последнего администратора.',
                responses: OpenApiSchemaBuilder::jsonResponse(
                    Response::HTTP_OK,
                    OpenApiSchemaBuilder::objectSchema([
                        'message' => ['type' => 'string'],
                        'user' => OpenApiSchemaBuilder::objectSchema([
                            'id' => ['type' => 'string', 'format' => 'uuid'],
                            'email' => ['type' => 'string', 'format' => 'email'],
                            'roles' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ]),
                    ], ['message']),
                    'Роль снята или пользователь не был администратором',
                ) + [
                    Response::HTTP_CONFLICT => ['description' => 'Нельзя снять роль последнего администратора'],
                ] + OpenApiSchemaBuilder::errorResponses(),
                parameters: [$userIdParam],
                security: OpenApiSchemaBuilder::jwtSecurity(),
            ),
        ));
    }

    private function addWikiLinkPaths(Paths $paths): void
    {
        $noteIdParam = OpenApiSchemaBuilder::uuidPathParam('id', 'UUID заметки');

        $paths->addPath('/api/notes/{id}/graph', (new PathItem())->withGet(
            OpenApiSchemaBuilder::operation(
                operationId: 'notes_graph_get',
                tags: ['WikiLinks'],
                summary: 'Граф wiki-ссылок заметки',
                description: 'Возвращает локальный subgraph связей заметки (до 120 узлов).',
                responses: OpenApiSchemaBuilder::jsonResponse(
                    Response::HTTP_OK,
                    OpenApiSchemaBuilder::objectSchema([
                        'nodes' => [
                            'type' => 'array',
                            'items' => OpenApiSchemaBuilder::objectSchema([
                                'id' => ['type' => 'string', 'format' => 'uuid'],
                                'title' => ['type' => 'string'],
                                'folderId' => ['type' => 'string', 'format' => 'uuid', 'nullable' => true],
                                'isFavorite' => ['type' => 'boolean'],
                            ]),
                        ],
                        'edges' => [
                            'type' => 'array',
                            'items' => OpenApiSchemaBuilder::objectSchema([
                                'id' => ['type' => 'string'],
                                'source' => ['type' => 'string', 'format' => 'uuid'],
                                'target' => ['type' => 'string', 'format' => 'uuid'],
                                'aliases' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string', 'nullable' => true],
                                ],
                            ]),
                        ],
                        'truncated' => ['type' => 'boolean'],
                        'frontierNodeIds' => [
                            'type' => 'array',
                            'items' => ['type' => 'string', 'format' => 'uuid'],
                        ],
                    ], ['nodes', 'edges', 'truncated', 'frontierNodeIds']),
                    'Subgraph wiki-связей',
                ) + OpenApiSchemaBuilder::errorResponses(),
                parameters: [
                    $noteIdParam,
                    OpenApiSchemaBuilder::queryIntParam('depth', 'Глубина обхода (1–3)', 1),
                    OpenApiSchemaBuilder::queryStringParam('direction', 'Направление: both, outgoing, incoming', 'both'),
                ],
                security: OpenApiSchemaBuilder::jwtSecurity(),
            ),
        ));

        $paths->addPath('/api/notes/resolve-wikilinks', (new PathItem())->withPost(
            OpenApiSchemaBuilder::operation(
                operationId: 'notes_resolve_wikilinks_post',
                tags: ['WikiLinks'],
                summary: 'Разрешение wiki-ссылок по ID',
                description: 'По списку UUID возвращает title и updatedAt активных заметок текущего пользователя.',
                responses: OpenApiSchemaBuilder::jsonResponse(
                    Response::HTTP_OK,
                    [
                        'type' => 'object',
                        'additionalProperties' => [
                            'oneOf' => [
                                ['type' => 'null'],
                                OpenApiSchemaBuilder::objectSchema([
                                    'id' => ['type' => 'string', 'format' => 'uuid'],
                                    'title' => ['type' => 'string'],
                                    'updatedAt' => ['type' => 'string', 'format' => 'date-time'],
                                ], ['id', 'title', 'updatedAt']),
                            ],
                        ],
                    ],
                    'Карта id → данные заметки или null',
                ) + OpenApiSchemaBuilder::errorResponses(),
                requestBody: OpenApiSchemaBuilder::jsonRequestBody(
                    OpenApiSchemaBuilder::objectSchema([
                        'ids' => [
                            'type' => 'array',
                            'items' => ['type' => 'string', 'format' => 'uuid'],
                        ],
                    ], ['ids']),
                ),
                security: OpenApiSchemaBuilder::jwtSecurity(),
            ),
        ));
    }
}
