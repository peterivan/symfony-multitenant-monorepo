<?php

declare(strict_types=1);

namespace App\Http\Boundary;

use App\Tenant\Boundary\TenantBoundaryGuard;
use App\Tenant\Exception\TenantConnectivityException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Enforces the tenant-facing route boundary (ADR-003).
 *
 * Runs at kernel.request priority 16: after tenant resolution
 * ({@see \App\EventSubscriber\TenantSubscriber}, priority 20) and before
 * Symfony's security firewall (priority 8), so tenant-local authentication and
 * authorization never execute without a valid active tenant context.
 *
 * On a tenant-facing route lacking a resolved/active/unambiguous/establishable
 * tenant context, the request fails closed with a uniform, non-tenant-revealing
 * 404 — no tenant-local authn/authz is attempted and no tenant-owned data is
 * accessed. The distinct internal cause is recorded only in diagnostic logs.
 */
final class TenantBoundaryEnforcementSubscriber implements EventSubscriberInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly RouteBoundaryClassifier $classifier,
        private readonly TenantBoundaryGuard $guard,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 16],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$this->classifier->requestIsTenantFacing($request)) {
            return;
        }

        try {
            $this->guard->assertEstablishedTenant();
        } catch (TenantConnectivityException $exception) {
            $this->logger->warning('Tenant-facing request failed closed at the boundary.', [
                'path' => $request->getPathInfo(),
                'cause' => $exception::class,
            ]);

            // Uniform, non-revealing response for every cause (missing, unresolved,
            // inactive, ambiguous, unestablishable): do not leak tenant existence.
            throw new NotFoundHttpException('Not Found', $exception);
        }
    }
}
