<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\NoteRepository;
use Symfony\Bundle\SecurityBundle\Security;

class NoteCollectionProvider implements ProviderInterface
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

        // Получаем только не удаленные заметки текущего пользователя
        return $this->noteRepository->findBy(
            ['user' => $user, 'deletedAt' => null],
            ['updatedAt' => 'DESC']
        );
    }
}
