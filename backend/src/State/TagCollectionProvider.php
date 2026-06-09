<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\TagRepository;
use Symfony\Bundle\SecurityBundle\Security;

class TagCollectionProvider implements ProviderInterface
{
    public function __construct(
        private TagRepository $tagRepository,
        private Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        return $this->tagRepository->findBy(
            ['user' => $user],
            ['name' => 'ASC']
        );
    }
}
