<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\TagRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class TagCollectionProvider implements ProviderInterface
{
    public function __construct(
        private TagRepository $tagRepository,
        private Security $security,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        $request = $this->requestStack->getCurrentRequest();
        $folderId = $request?->query->get('folderId');
        $tags = $request?->query->all('tags') ?? [];

        if (($folderId !== null && $folderId !== '') || !empty($tags)) {
            return $this->tagRepository->findDistinctForUserNotes($user, $folderId, $tags);
        }

        return $this->tagRepository->findBy(
            ['user' => $user],
            ['name' => 'ASC']
        );
    }
}
