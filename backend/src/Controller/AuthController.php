<?php

namespace App\Controller;

use App\Dto\ChangePasswordDto;
use App\Dto\UpdateUserSettingsDto;
use App\Entity\User;
use App\Feature\AuthFeature;
use App\Service\UserSettingsResolver;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private JWTTokenManagerInterface $jwtManager,
        private UserSettingsResolver $userSettingsResolver,
        private SerializerInterface $serializer,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private AuthFeature $authFeature,
        private int $refreshTokenTtl,
    ) {
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        if ($response = $this->denyWhenAuthDisabled()) {
            return $response;
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'error' => 'Email и пароль обязательны'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPlainPassword($data['password']);

        $errors = $this->validator->validate($user, groups: ['Default', 'user:create']);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $user->getEmail(),
        ]);
        if ($existingUser !== null) {
            return $this->json(['error' => 'Email уже занят'], Response::HTTP_CONFLICT);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPlainPassword());
        $user->setPassword($hashedPassword);
        $user->eraseCredentials();

        $userCount = $this->entityManager->getRepository(User::class)->count([]);
        if ($userCount === 0) {
            $user->setRoles(['ROLE_ADMIN']);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->jwtManager->create($user);
        $refreshToken = $this->createRefreshTokenForUser($user);

        return $this->json([
            'token' => $token,
            'refreshToken' => $refreshToken,
            'user' => $this->serializeUser($user),
        ], Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->json(['error' => 'Не авторизован'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($this->serializeUser($user));
    }

    #[Route('/settings', name: 'api_auth_settings', methods: ['PATCH'])]
    public function updateSettings(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Не авторизован'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        /** @var UpdateUserSettingsDto $dto */
        $dto = $this->serializer->deserialize(
            json_encode($data),
            UpdateUserSettingsDto::class,
            'json',
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('autosaveDelaySeconds', $data)) {
            $user->setAutosaveDelaySeconds($dto->autosaveDelaySeconds);
        }

        if (array_key_exists('versionConsolidationWindowMinutes', $data)) {
            $user->setVersionConsolidationWindowMinutes($dto->versionConsolidationWindowMinutes);
        }

        $entityErrors = $this->validator->validate($user);
        if (count($entityErrors) > 0) {
            $errorMessages = [];
            foreach ($entityErrors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json($this->serializeUser($user));
    }

    #[Route('/change-password', name: 'api_auth_change_password', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        if ($response = $this->denyWhenAuthDisabled()) {
            return $response;
        }

        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Не авторизован'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        /** @var ChangePasswordDto $dto */
        $dto = $this->serializer->deserialize(
            json_encode($data),
            ChangePasswordDto::class,
            'json',
        );

        if (!$this->passwordHasher->isPasswordValid($user, $dto->currentPassword ?? '')) {
            return $this->json(
                ['errors' => ['currentPassword' => 'Неверный текущий пароль']],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->newPassword);
        $user->setPassword($hashedPassword);
        $this->entityManager->flush();

        return $this->json(['message' => 'Пароль успешно изменён']);
    }

    private function denyWhenAuthDisabled(): ?JsonResponse
    {
        if (!$this->authFeature->isEnabled()) {
            return $this->json(
                ['error' => 'Authentication is disabled in single-user mode'],
                Response::HTTP_NOT_FOUND,
            );
        }

        return null;
    }

    private function createRefreshTokenForUser(User $user): string
    {
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTokenTtl);
        $this->refreshTokenManager->save($refreshToken);

        return (string) $refreshToken->getRefreshToken();
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId()->toRfc4122(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'isActive' => $user->isActive(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
            'settings' => $this->userSettingsResolver->getSettingsForUser($user),
            'defaults' => $this->userSettingsResolver->getDefaults(),
        ];
    }
}
