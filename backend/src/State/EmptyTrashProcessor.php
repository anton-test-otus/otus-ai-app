<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Repository\NoteRepository;
use App\Security\AuthenticatedUserAssert;
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
        $user = AuthenticatedUserAssert::requirePersistedUser($this->security->getUser());

        foreach ($this->noteRepository->findAllDeletedByUser($user) as $note) {
            $this->em->remove($note);
        }

        $this->em->flush();

        return null;
    }
}
