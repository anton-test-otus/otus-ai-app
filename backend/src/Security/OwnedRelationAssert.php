<?php

namespace App\Security;

use App\Entity\Folder;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

final class OwnedRelationAssert
{
    public static function assertFolder(?Folder $folder, UserInterface $current): void
    {
        if (null === $folder) {
            return;
        }

        self::assertSameUser($folder->getUser(), $current, 'Папка не принадлежит текущему пользователю');
    }

    public static function assertParentFolder(?Folder $parent, UserInterface $current): void
    {
        if (null === $parent) {
            return;
        }

        self::assertSameUser($parent->getUser(), $current, 'Родительская папка не принадлежит текущему пользователю');

        if (null !== $parent->getDeletedAt()) {
            throw new UnprocessableEntityHttpException('Родительская папка удалена');
        }
    }

    public static function assertTagOwner(?User $owner, UserInterface $current): void
    {
        self::assertSameUser($owner, $current, 'Тег не принадлежит текущему пользователю');
    }

    private static function assertSameUser(?User $owner, UserInterface $current, string $message): void
    {
        if (!$current instanceof User) {
            throw new UnprocessableEntityHttpException($message);
        }

        if (
            null === $owner
            || null === $owner->getId()
            || null === $current->getId()
            || !$owner->getId()->equals($current->getId())
        ) {
            throw new UnprocessableEntityHttpException($message);
        }
    }
}
