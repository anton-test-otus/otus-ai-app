<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tags')]
#[IsGranted('ROLE_USER')]
class TagController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TagRepository $tagRepository,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'api_tags_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $tags = $this->tagRepository->findBy(['user' => $user], ['name' => 'ASC']);

        return $this->json($tags, 200, [], ['groups' => ['tag:read']]);
    }

    #[Route('', name: 'api_tags_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        if (empty($data['name'])) {
            return $this->json(['error' => 'Название тега обязательно'], 400);
        }

        $existing = $this->tagRepository->findOneBy([
            'user' => $user,
            'name' => $data['name']
        ]);

        if ($existing) {
            return $this->json(['error' => 'Тег с таким названием уже существует'], 409);
        }

        $tag = new Tag();
        $tag->setUser($user);
        $tag->setName($data['name']);

        $errors = $this->validator->validate($tag);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $this->json($tag, 201, [], ['groups' => ['tag:read']]);
    }

    #[Route('/{id}', name: 'api_tags_update', methods: ['PUT', 'PATCH'])]
    public function update(Tag $tag, Request $request): JsonResponse
    {
        if ($tag->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Доступ запрещен'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $existing = $this->tagRepository->findOneBy([
                'user' => $this->getUser(),
                'name' => $data['name']
            ]);

            if ($existing && $existing->getId() !== $tag->getId()) {
                return $this->json(['error' => 'Тег с таким названием уже существует'], 409);
            }

            $tag->setName($data['name']);
        }

        $errors = $this->validator->validate($tag);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        $this->entityManager->flush();

        return $this->json($tag, 200, [], ['groups' => ['tag:read']]);
    }

    #[Route('/{id}', name: 'api_tags_delete', methods: ['DELETE'])]
    public function delete(Tag $tag): JsonResponse
    {
        if ($tag->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Доступ запрещен'], 403);
        }

        $this->entityManager->remove($tag);
        $this->entityManager->flush();

        return $this->json(['message' => 'Тег удален'], 204);
    }

    #[Route('/{id}/notes', name: 'api_tags_notes', methods: ['GET'])]
    public function getNotes(Tag $tag, Request $request): JsonResponse
    {
        if ($tag->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Доступ запрещен'], 403);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = min(100, max(1, (int) $request->query->get('perPage', 20)));

        $notes = $tag->getNotes()
            ->filter(fn($note) => $note->getDeletedAt() === null)
            ->slice(($page - 1) * $perPage, $perPage);

        $total = $tag->getNotes()
            ->filter(fn($note) => $note->getDeletedAt() === null)
            ->count();

        return $this->json([
            'data' => array_values($notes->toArray()),
            'meta' => [
                'currentPage' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => (int) ceil($total / $perPage),
            ],
        ], 200, [], ['groups' => ['note:read']]);
    }
}
