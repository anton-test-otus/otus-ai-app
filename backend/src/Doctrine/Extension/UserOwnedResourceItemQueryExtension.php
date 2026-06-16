<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Folder;
use App\Entity\Note;
use App\Entity\NoteVersion;
use App\Entity\Tag;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Filters API Platform item queries so users only load their own resources.
 * Foreign or soft-deleted (on GET) entities are not found → HTTP 404.
 */
final class UserOwnedResourceItemQueryExtension implements QueryItemExtensionInterface
{
    public function __construct(
        private Security $security
    ) {
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $user = $this->security->getUser();
        if (null === $user) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $isGet = 'GET' === $operation?->getMethod();

        match ($resourceClass) {
            Note::class => $this->applyNoteFilter($queryBuilder, $rootAlias, $user, $isGet),
            Folder::class => $this->applyFolderFilter($queryBuilder, $rootAlias, $user, $isGet),
            Tag::class => $this->applyTagFilter($queryBuilder, $rootAlias, $user),
            NoteVersion::class => $this->applyNoteVersionFilter($queryBuilder, $rootAlias, $user),
            default => null,
        };
    }

    private function applyNoteFilter(QueryBuilder $queryBuilder, string $rootAlias, object $user, bool $isGet): void
    {
        $queryBuilder
            ->andWhere(sprintf('%s.user = :owned_user', $rootAlias))
            ->setParameter('owned_user', $user);

        if ($isGet) {
            $queryBuilder->andWhere(sprintf('%s.deletedAt IS NULL', $rootAlias));
        }
    }

    private function applyFolderFilter(QueryBuilder $queryBuilder, string $rootAlias, object $user, bool $isGet): void
    {
        $queryBuilder
            ->andWhere(sprintf('%s.user = :owned_user', $rootAlias))
            ->setParameter('owned_user', $user);

        if ($isGet) {
            $queryBuilder->andWhere(sprintf('%s.deletedAt IS NULL', $rootAlias));
        }
    }

    private function applyTagFilter(QueryBuilder $queryBuilder, string $rootAlias, object $user): void
    {
        $queryBuilder
            ->andWhere(sprintf('%s.user = :owned_user', $rootAlias))
            ->setParameter('owned_user', $user);
    }

    private function applyNoteVersionFilter(QueryBuilder $queryBuilder, string $rootAlias, object $user): void
    {
        $queryBuilder
            ->innerJoin(sprintf('%s.note', $rootAlias), 'owned_nv_note')
            ->andWhere('owned_nv_note.user = :owned_user')
            ->setParameter('owned_user', $user);
    }

}
