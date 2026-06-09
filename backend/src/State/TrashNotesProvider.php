<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\NoteRepository;
use Symfony\Bundle\SecurityBundle\Security;

class TrashNotesProvider implements ProviderInterface
{
    public function __construct(
        private NoteRepository $noteRepository,
        private Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        $page = $context['filters']['page'] ?? 1;
        $perPage = $context['filters']['itemsPerPage'] ?? 20;

        return $this->noteRepository->findDeletedNotes($user, $page, $perPage);
    }
}
