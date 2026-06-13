<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Note;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RestoreNoteProcessor implements ProcessorInterface
{
    public function __construct(
        private NoteRepository $noteRepository,
        private EntityManagerInterface $em,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Note
    {
        $user = $this->security->getUser();
        $noteId = $uriVariables['id'] ?? null;

        if (!$noteId) {
            throw new BadRequestHttpException('Note ID is required');
        }

        $note = $this->noteRepository->findOneBy([
            'id' => $noteId,
            'user' => $user,
        ]);

        if (!$note) {
            throw new NotFoundHttpException('Не найдена');
        }

        if (!$note->getDeletedAt()) {
            throw new BadRequestHttpException('Note is not in trash');
        }

        $note->setDeletedAt(null);
        $this->em->flush();

        return $note;
    }
}
