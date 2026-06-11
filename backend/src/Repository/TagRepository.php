<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * Теги, встречающиеся в заметках пользователя с учётом папки и уже выбранных тегов (логика И).
     *
     * @param string[] $tagIds
     * @return Tag[]
     */
    public function findDistinctForUserNotes($user, ?string $folderId = null, array $tagIds = []): array
    {
        $qb = $this->createQueryBuilder('tag')
            ->innerJoin('tag.notes', 'n')
            ->where('tag.user = :user')
            ->andWhere('n.deletedAt IS NULL')
            ->setParameter('user', $user);

        if ($folderId !== null && $folderId !== '') {
            $qb->andWhere('n.folder = :folderId')
                ->setParameter('folderId', $folderId);
        }

        $normalizedTagIds = array_values(array_unique(array_filter(
            $tagIds,
            static fn ($id) => is_string($id) && $id !== ''
        )));

        foreach ($normalizedTagIds as $index => $tagId) {
            $alias = 'filterTag' . $index;
            $qb->innerJoin('n.tags', $alias)
                ->andWhere($alias . '.id = :filterTagId' . $index)
                ->setParameter('filterTagId' . $index, $tagId);
        }

        return $qb->distinct()
            ->orderBy('tag.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
