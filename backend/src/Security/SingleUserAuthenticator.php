<?php

namespace App\Security;

use App\Feature\AuthFeature;
use App\Service\SingleUserProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Authenticates every API request as the sole local user when JWT auth is disabled.
 */
final class SingleUserAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private AuthFeature $authFeature,
        private SingleUserProvider $singleUserProvider,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if ($this->authFeature->isEnabled()) {
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        return new SelfValidatingPassport(
            new UserBadge($this->singleUserProvider->getSingleUser()->getUserIdentifier(), function (string $identifier) {
                return $this->singleUserProvider->getSingleUser();
            }),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $previous = $exception->getPrevious();
        $message = $previous instanceof UserNotFoundException
            ? $previous->getMessage()
            : 'Authentication failed';

        return new JsonResponse(['error' => $message], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
