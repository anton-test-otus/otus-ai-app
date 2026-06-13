<?php

namespace App\EventListener;

use App\Entity\Note;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 64)]
final class NoteNotFoundExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $exception = $event->getThrowable();
        if (!$exception instanceof NotFoundHttpException) {
            return;
        }

        if (Note::class !== $event->getRequest()->attributes->get('_api_resource_class')) {
            return;
        }

        if ('Не найдена' === $exception->getMessage()) {
            return;
        }

        $event->setThrowable(new NotFoundHttpException('Не найдена', $exception));
    }
}
