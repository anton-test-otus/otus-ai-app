<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class SingleUserProvider
{
    public function __construct(
        private UserRepository $userRepository,
        private string $singleUserEmail,
    ) {
    }

    public function getSingleUser(): User
    {
        $user = $this->userRepository->findOneBy(['email' => $this->singleUserEmail]);

        if ($user === null) {
            throw new UserNotFoundException(sprintf(
                'Single-user mode is active but user "%s" was not found. Run app:ensure-single-user.',
                $this->singleUserEmail,
            ));
        }

        return $user;
    }
}
