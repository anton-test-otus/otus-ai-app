<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/users', name: 'api_admin_users_list', methods: ['GET'])]
    public function listUsers(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = min(100, max(1, (int) $request->query->get('perPage', 20)));
        $query = $request->query->get('q', '');

        $repository = $this->entityManager->getRepository(User::class);

        if ($query) {
            $users = $repository->searchUsers($query, $page, $perPage);
            $total = $repository->countSearchResults($query);
        } else {
            $qb = $repository->createQueryBuilder('u')
                ->orderBy('u.createdAt', 'DESC')
                ->setFirstResult(($page - 1) * $perPage)
                ->setMaxResults($perPage);

            $users = $qb->getQuery()->getResult();
            $total = $repository->count([]);
        }

        $usersData = array_map(function (User $user) use ($repository) {
            $stats = $repository->getUserStatistics($user);
            
            return [
                'id' => $user->getId()->toRfc4122(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'isActive' => $user->isActive(),
                'createdAt' => $user->getCreatedAt()?->format('c'),
                'statistics' => [
                    'notesCount' => $stats['notesCount'],
                    'foldersCount' => $stats['foldersCount'],
                    'tagsCount' => $stats['tagsCount'],
                    'lastActivity' => $stats['lastActivity']?->format('c'),
                    'storageSize' => $stats['storageSize'],
                ],
            ];
        }, $users);

        return $this->json([
            'data' => $usersData,
            'meta' => [
                'currentPage' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => (int) ceil($total / $perPage),
            ]
        ]);
    }

    #[Route('/users/{id}', name: 'api_admin_users_get', methods: ['GET'])]
    public function getUserDetails(User $user): JsonResponse
    {
        $repository = $this->entityManager->getRepository(User::class);
        $stats = $repository->getUserStatistics($user);

        return $this->json([
            'id' => $user->getId()->toRfc4122(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'isActive' => $user->isActive(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
            'statistics' => [
                'notesCount' => $stats['notesCount'],
                'foldersCount' => $stats['foldersCount'],
                'tagsCount' => $stats['tagsCount'],
                'lastActivity' => $stats['lastActivity']?->format('c'),
                'storageSize' => $stats['storageSize'],
            ],
        ]);
    }

    #[Route('/users/{id}/enable', name: 'api_admin_users_enable', methods: ['PATCH'])]
    public function enableUser(User $user): JsonResponse
    {
        $user->setIsActive(true);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Пользователь активирован',
            'user' => [
                'id' => $user->getId()->toRfc4122(),
                'isActive' => $user->isActive(),
            ]
        ]);
    }

    #[Route('/users/{id}/disable', name: 'api_admin_users_disable', methods: ['PATCH'])]
    public function disableUser(User $user): JsonResponse
    {
        $user->setIsActive(false);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Пользователь деактивирован',
            'user' => [
                'id' => $user->getId()->toRfc4122(),
                'isActive' => $user->isActive(),
            ]
        ]);
    }

    #[Route('/users/{id}', name: 'api_admin_users_delete', methods: ['DELETE'])]
    public function deleteUser(User $user): JsonResponse
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Пользователь и все его данные удалены'
        ]);
    }

    #[Route('/users/{id}/promote', name: 'api_admin_users_promote', methods: ['PATCH'])]
    public function promoteUser(User $user): JsonResponse
    {
        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            return $this->json([
                'message' => 'Пользователь уже имеет роль администратора'
            ], Response::HTTP_OK);
        }

        $roles[] = 'ROLE_ADMIN';
        $user->setRoles($roles);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Пользователь назначен администратором',
            'user' => [
                'id' => $user->getId()->toRfc4122(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ]
        ]);
    }

    #[Route('/users/{id}/demote', name: 'api_admin_users_demote', methods: ['PATCH'])]
    public function demoteUser(User $user): JsonResponse
    {
        $roles = $user->getRoles();
        
        if (!in_array('ROLE_ADMIN', $roles)) {
            return $this->json([
                'message' => 'Пользователь не является администратором'
            ], Response::HTTP_OK);
        }

        $roles = array_filter($roles, fn($role) => $role !== 'ROLE_ADMIN');
        $user->setRoles(array_values($roles));
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Роль администратора снята с пользователя',
            'user' => [
                'id' => $user->getId()->toRfc4122(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ]
        ]);
    }
}
