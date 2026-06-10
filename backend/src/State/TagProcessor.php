<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class TagProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private PersistProcessor $persistProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Tag
    {
        if (!$data instanceof Tag) {
            return null;
        }

        $user = $this->security->getUser();
        
        // Для новых тегов устанавливаем пользователя
        if (!$data->getId()) {
            $data->setUser($user);
        }

        // Удаление
        if ($operation->getMethod() === 'DELETE') {
            $this->em->remove($data);
            $this->em->flush();
            return null;
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        return $result instanceof Tag ? $result : null;
    }
}
