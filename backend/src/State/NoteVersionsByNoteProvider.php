<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\NoteRepository;
use App\Repository\NoteVersionRepository;
use App\Security\AuthenticatedUserAssert;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NoteVersionsByNoteProvider implements ProviderInterface
{
    public function __construct(
        private NoteVersionRepository $versionRepository,
        private NoteRepository $noteRepository,
        private Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = AuthenticatedUserAssert::requirePersistedUser($this->security->getUser());

        $noteId = $uriVariables['noteId'] ?? null;
        if (!$noteId) {
            throw new NotFoundHttpException('Note ID not provided');
        }

        $note = $this->noteRepository->find($noteId);
        if (!$note) {
            throw new NotFoundHttpException('Не найдена');
        }

        if ($note->getUser() !== $user) {
            throw new AccessDeniedHttpException('You do not have access to this note');
        }

        return $this->versionRepository->findByNote($note);
    }
}
