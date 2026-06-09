<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\NoteLinkRepository;
use Symfony\Bundle\SecurityBundle\Security;

class NoteLinkCollectionProvider implements ProviderInterface
{
    public function __construct(
        private NoteLinkRepository $linkRepository,
        private Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        // Возвращаем только линки между заметками текущего пользователя
        $qb = $this->linkRepository->createQueryBuilder('l')
            ->join('l.sourceNote', 's')
            ->join('l.targetNote', 't')
            ->where('s.user = :user')
            ->andWhere('t.user = :user')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }
}
