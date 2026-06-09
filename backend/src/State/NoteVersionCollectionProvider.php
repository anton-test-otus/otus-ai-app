<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\NoteVersion;
use App\Repository\NoteVersionRepository;
use Symfony\Bundle\SecurityBundle\Security;

class NoteVersionCollectionProvider implements ProviderInterface
{
    public function __construct(
        private NoteVersionRepository $versionRepository,
        private Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        // Возвращаем версии только для заметок текущего пользователя
        $qb = $this->versionRepository->createQueryBuilder('v')
            ->join('v.note', 'n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('v.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
