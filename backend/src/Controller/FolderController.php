<?php

namespace App\Controller;

use App\Entity\Folder;
use App\Repository\FolderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/folders')]
#[IsGranted('ROLE_USER')]
class FolderController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FolderRepository $folderRepository,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'api_folders_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $folders = $this->folderRepository->findBy(
            ['user' => $user, 'deletedAt' => null],
            ['name' => 'ASC']
        );

        $tree = $this->buildTree($folders);

        return $this->json($tree, 200, [], ['groups' => ['folder:read']]);
    }

    #[Route('', name: 'api_folders_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        if (empty($data['name'])) {
            return $this->json(['error' => 'Название папки обязательно'], 400);
        }

        $folder = new Folder();
        $folder->setUser($user);
        $folder->setName($data['name']);

        if (!empty($data['parentId'])) {
            $parent = $this->folderRepository->find($data['parentId']);
            if (!$parent || $parent->getUser() !== $user) {
                return $this->json(['error' => 'Родительская папка не найдена'], 404);
            }
            $folder->setParent($parent);
        }

        $errors = $this->validator->validate($folder);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $this->entityManager->persist($folder);
        $this->entityManager->flush();

        return $this->json($folder, 201, [], ['groups' => ['folder:read']]);
    }

    #[Route('/{id}', name: 'api_folders_update', methods: ['PUT', 'PATCH'])]
    public function update(Folder $folder, Request $request): JsonResponse
    {
        if ($folder->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Доступ запрещен'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $folder->setName($data['name']);
        }

        if (isset($data['parentId'])) {
            if ($data['parentId'] === null) {
                $folder->setParent(null);
            } else {
                $parent = $this->folderRepository->find($data['parentId']);
                if (!$parent || $parent->getUser() !== $this->getUser()) {
                    return $this->json(['error' => 'Родительская папка не найдена'], 404);
                }
                if ($parent->getId() === $folder->getId()) {
                    return $this->json(['error' => 'Папка не может быть родителем самой себе'], 400);
                }
                $folder->setParent($parent);
            }
        }

        $errors = $this->validator->validate($folder);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $this->entityManager->flush();

        return $this->json($folder, 200, [], ['groups' => ['folder:read']]);
    }

    #[Route('/{id}/count', name: 'api_folders_count', methods: ['GET'])]
    public function count(Folder $folder): JsonResponse
    {
        if ($folder->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Доступ запрещен'], 403);
        }

        $counts = $this->countFolderContents($folder);

        return $this->json($counts);
    }

    #[Route('/{id}', name: 'api_folders_delete', methods: ['DELETE'])]
    public function delete(Folder $folder): JsonResponse
    {
        if ($folder->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Доступ запрещен'], 403);
        }

        $now = new \DateTimeImmutable();
        $this->softDeleteFolderRecursive($folder, $now);

        $this->entityManager->flush();

        return $this->json(['message' => 'Папка и её содержимое перемещены в корзину'], 200);
    }

    private function buildTree(array $folders, ?Folder $parent = null): array
    {
        $tree = [];
        foreach ($folders as $folder) {
            if ($folder->getParent() === $parent) {
                $folderData = [
                    'id' => $folder->getId(),
                    'name' => $folder->getName(),
                    'parent' => $folder->getParent()?->getId(),
                    'children' => $this->buildTree($folders, $folder),
                ];
                $tree[] = $folderData;
            }
        }
        return $tree;
    }

    private function countFolderContents(Folder $folder): array
    {
        $notesCount = $folder->getNotes()
            ->filter(fn($note) => $note->getDeletedAt() === null)
            ->count();

        $foldersCount = 0;
        $totalNotes = $notesCount;

        foreach ($folder->getChildren() as $child) {
            if ($child->getDeletedAt() === null) {
                $foldersCount++;
                $childCounts = $this->countFolderContents($child);
                $foldersCount += $childCounts['folders'];
                $totalNotes += $childCounts['notes'];
            }
        }

        return [
            'folders' => $foldersCount,
            'notes' => $totalNotes,
        ];
    }

    private function softDeleteFolderRecursive(Folder $folder, \DateTimeImmutable $deletedAt): void
    {
        $folder->setDeletedAt($deletedAt);

        foreach ($folder->getNotes() as $note) {
            if ($note->getDeletedAt() === null) {
                $note->setDeletedAt($deletedAt);
            }
        }

        foreach ($folder->getChildren() as $child) {
            if ($child->getDeletedAt() === null) {
                $this->softDeleteFolderRecursive($child, $deletedAt);
            }
        }
    }
}
