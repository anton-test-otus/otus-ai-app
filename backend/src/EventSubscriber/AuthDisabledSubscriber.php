<?php

namespace App\EventSubscriber;

use App\Feature\AuthFeature;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Blocks JWT login/register/refresh when the app runs in single-user mode.
 */
final class AuthDisabledSubscriber implements EventSubscriberInterface
{
    private const BLOCKED_PATHS = [
        '/api/auth/login' => ['POST'],
        '/api/auth/register' => ['POST'],
        '/api/auth/refresh' => ['POST'],
    ];

    public function __construct(
        private AuthFeature $authFeature,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $this->authFeature->isEnabled()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        $allowedMethods = self::BLOCKED_PATHS[$path] ?? null;

        if ($allowedMethods === null || !in_array($request->getMethod(), $allowedMethods, true)) {
            return;
        }

        $event->setResponse(new JsonResponse(
            ['error' => 'Authentication is disabled in single-user mode'],
            Response::HTTP_NOT_FOUND,
        ));
    }
}
