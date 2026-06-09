<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class NoteProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Note
    {
        if (!$data instanceof Note) {
            return null;
        }

        $user = $this->security->getUser();
        
        // Для новых заметок устанавливаем пользователя
        if (!$data->getId()) {
            $data->setUser($user);
        }

        // Обработка DELETE операций
        if ($operation->getMethod() === 'DELETE') {
            // Если заметка уже в корзине (deletedAt установлен) - удалить навсегда
            if ($data->getDeletedAt()) {
                $this->em->remove($data);
                $this->em->flush();
                return null;
            }
            
            // Иначе - soft delete (переместить в корзину)
            $data->setDeletedAt(new \DateTimeImmutable());
            $this->em->flush();
            return $data;
        }

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
