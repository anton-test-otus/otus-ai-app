<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Notes and related resources are scoped to a user row in the database.
 */
final class AuthenticatedUserAssert
{
    public static function requirePersistedUser(?UserInterface $user): User
    {
        if (!$user instanceof User || $user->getId() === null) {
            throw new AccessDeniedHttpException('Access denied');
        }

        return $user;
    }
}
