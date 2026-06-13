<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\NoteLink;
use App\Security\ResourceOwnershipAssert;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class NoteLinkProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?NoteLink
    {
        if (!$data instanceof NoteLink) {
            return null;
        }

        $user = $this->security->getUser();

        ResourceOwnershipAssert::assertOwnedBy($data->getSourceNote()?->getUser(), $user);
        ResourceOwnershipAssert::assertOwnedBy($data->getTargetNote()?->getUser(), $user);

        // Удаление
        if ($operation->getMethod() === 'DELETE') {
            $this->em->remove($data);
            $this->em->flush();
            return null;
        }

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
