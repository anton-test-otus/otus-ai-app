<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class EmptyTrashProcessor implements ProcessorInterface
{
    public function __construct(
        private NoteRepository $noteRepository,
        private EntityManagerInterface $em,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $user = $this->security->getUser();
        
        $deletedNotes = $this->noteRepository->findBy([
            'user' => $user,
        ]);

        foreach ($deletedNotes as $note) {
            if ($note->getDeletedAt()) {
                $this->em->remove($note);
            }
        }

        $this->em->flush();

        return null;
    }
}
