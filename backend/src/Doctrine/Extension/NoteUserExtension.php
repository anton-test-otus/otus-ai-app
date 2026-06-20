<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Note;
use Doctrine\ORM\QueryBuilder;
use App\Security\AuthenticatedUserAssert;
use Symfony\Bundle\SecurityBundle\Security;

final class NoteUserExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private Security $security
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (Note::class !== $resourceClass) {
            return;
        }

        if ('trash_list' === $operation?->getName()) {
            return;
        }

        $user = AuthenticatedUserAssert::requirePersistedUser($this->security->getUser());

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere(sprintf('%s.user = :note_user', $rootAlias))
            ->andWhere(sprintf('%s.deletedAt IS NULL', $rootAlias))
            ->setParameter('note_user', $user);
    }
}
