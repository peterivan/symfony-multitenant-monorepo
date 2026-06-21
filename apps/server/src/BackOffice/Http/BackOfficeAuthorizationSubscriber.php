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
 * (ADR-002, ADR-007).
 *
 * Runs at kernel.request priority 6: after the platform firewall (8) has restored
 * or established platform authentication state, so the decision reflects the real
 * security token. Access is decided SOLELY by the {@see PlatformOperatorAuthorizer};
 * tenant context and tenant-local identity are never consulted, so a tenant-local
 * identity can neither grant access nor auto-inherit platform-operator privileges.
 * The platform login check path stays reachable so operators can authenticate.
 * Non-operators fail closed with 403.
 */
final class BackOfficeAuthorizationSubscriber implements EventSubscriberInterface
{
    /**
     * Route that must stay reachable without platform-operator authorization so
     * operators can submit credentials.
     */
    private const string LOGIN_ROUTE = 'back_office_login';

    public function __construct(
        private readonly RouteBoundaryClassifier $classifier,
        private readonly PlatformOperatorAuthorizer $authorizer,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 6],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (RouteBoundary::BackOffice !== $this->classifier->classifyRequest($request)) {
            return;
        }

        if (self::LOGIN_ROUTE === $request->attributes->get('_route')) {
            return;
        }

        if (!$this->authorizer->isPlatformOperator()) {
            throw new AccessDeniedHttpException('Back Office access requires platform-operator authorization.');
        }
    }
}
