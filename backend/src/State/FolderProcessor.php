<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Folder;
use App\Security\ResourceOwnershipAssert;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class FolderProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private PersistProcessor $persistProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Folder
    {
        if (!$data instanceof Folder) {
            return null;
        }

        $user = $this->security->getUser();
        
        // Для новых папок устанавливаем пользователя
        if (!$data->getId()) {
            $data->setUser($user);
        } else {
            ResourceOwnershipAssert::assertOwnedBy($data->getUser(), $user);
        }

        // Soft delete для DELETE операций
        if ($operation->getMethod() === 'DELETE') {
            $data->setDeletedAt(new \DateTimeImmutable());
            $this->em->flush();
            return $data;
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        return $result instanceof Folder ? $result : null;
    }
}
