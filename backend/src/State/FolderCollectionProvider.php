<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\FolderRepository;
use Symfony\Bundle\SecurityBundle\Security;

class FolderCollectionProvider implements ProviderInterface
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

        return $this->folderRepository->findBy(
            ['user' => $user, 'deletedAt' => null],
            ['name' => 'ASC']
        );
    }
}
