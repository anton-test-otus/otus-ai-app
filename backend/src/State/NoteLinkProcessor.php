<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\NoteLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
        
        // Валидация: можно создавать линки только между своими заметками
        $sourceNote = $data->getSourceNote();
        $targetNote = $data->getTargetNote();

        if ($sourceNote && $sourceNote->getUser() !== $user) {
            throw new AccessDeniedHttpException('Нет доступа к исходной заметке');
        }

        if ($targetNote && $targetNote->getUser() !== $user) {
            throw new AccessDeniedHttpException('Нет доступа к целевой заметке');
        }

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
