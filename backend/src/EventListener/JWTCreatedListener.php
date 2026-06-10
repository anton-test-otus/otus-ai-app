<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\UserSettingsResolver;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class JWTCreatedListener
{
    public function __construct(
        private UserSettingsResolver $userSettingsResolver,
    ) {
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $data['user'] = [
            'id' => $user->getId()->toRfc4122(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'isActive' => $user->isActive(),
            'settings' => $this->userSettingsResolver->getSettingsForUser($user),
            'defaults' => $this->userSettingsResolver->getDefaults(),
        ];

        $event->setData($data);
    }
}
