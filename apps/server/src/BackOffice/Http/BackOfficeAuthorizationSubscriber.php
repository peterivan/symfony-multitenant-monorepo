<?php

declare(strict_types=1);

namespace App\BackOffice\Http;

use App\BackOffice\Security\PlatformOperatorAuthorizer;
use App\Http\Boundary\RouteBoundary;
use App\Http\Boundary\RouteBoundaryClassifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Authorizes every Back Office request through the platform-operator boundary only
 * (ADR-002).
 *
 * Runs at kernel.request priority 14: after the Back Office boundary subscriber
 * (18) has guaranteed central-context entry. Access is decided SOLELY by the
 * {@see PlatformOperatorAuthorizer}; tenant context and tenant-local identity are
 * never consulted, so a tenant-local identity can neither grant access nor
 * auto-inherit platform-operator privileges. Non-operators fail closed with 403.
 */
final class BackOfficeAuthorizationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RouteBoundaryClassifier $classifier,
        private readonly PlatformOperatorAuthorizer $authorizer,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 14],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (RouteBoundary::BackOffice !== $this->classifier->classifyRequest($event->getRequest())) {
            return;
        }

        if (!$this->authorizer->isPlatformOperator()) {
            throw new AccessDeniedHttpException('Back Office access requires platform-operator authorization.');
        }
    }
}
