<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

final class ResourceOwnershipAssert
{
    public static function assertOwnedBy(?User $owner, ?UserInterface $current): void
    {
        if (!$current instanceof User) {
            throw new AccessDeniedHttpException('Access denied');
        }

        if (
            null === $owner
            || null === $owner->getId()
            || null === $current->getId()
            || !$owner->getId()->equals($current->getId())
        ) {
            throw new AccessDeniedHttpException('Access denied');
        }
    }
}
