<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Folder;
use App\Repository\FolderRepository;
use Symfony\Bundle\SecurityBundle\Security;

class FolderTreeProvider implements ProviderInterface
{
    public function __construct(
        private FolderRepository $folderRepository,
        private Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        $folders = $this->folderRepository->findBy(
            ['user' => $user, 'deletedAt' => null],
            ['name' => 'ASC']
        );

        // API Platform обернет массив в hydra:member автоматически
        return $this->buildTree($folders);
    }

    private function buildTree(array $folders, ?Folder $parent = null): array
    {
        $tree = [];
        foreach ($folders as $folder) {
            if ($folder->getParent() === $parent) {
                $tree[] = [
                    'id' => $folder->getId()->toRfc4122(),
                    'name' => $folder->getName(),
                    'parent' => $folder->getParent()?->getId()->toRfc4122(),
                    'children' => $this->buildTree($folders, $folder),
                ];
            }
        }
        return $tree;
    }
}
