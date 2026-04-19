<?php

namespace App\EventSubscriber;

use App\Service\UserActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserActivityLogger $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$request->isMethod('GET') || $response->getStatusCode() >= 400) {
            return;
        }

        $path = $request->getPathInfo();
        if (str_starts_with($path, '/_') || str_starts_with($path, '/api') || str_starts_with($path, '/admin')) {
            return;
        }

        // Skip AJAX and non-HTML responses
        if ($request->isXmlHttpRequest()) {
            return;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && !str_contains($contentType, 'text/html')) {
            return;
        }

        // Skip typical asset paths
        if (preg_match('/\.(css|js|map|png|jpg|jpeg|gif|webp|svg|ico|woff|woff2|ttf|eot)$/i', $path)) {
            return;
        }

        if ($response->isSuccessful()) {
            try {
                $this->logger->logFromRequest($request, 'VIEW_PAGE', 'Visited page: ' . $path);
            } catch (\Exception $e) {
                // Ignore logging errors silently to not break terminate
            }
        }
    }
}
