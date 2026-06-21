<?php

declare(strict_types=1);

namespace App\BackOffice\Http;

use App\Http\Boundary\RouteBoundary;
use App\Http\Boundary\RouteBoundaryClassifier;
use App\Tenant\Connection\TenantEntityManagerProvider;
use App\Tenant\TenantContext;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Enforces the Back Office boundary (ADR-002).
 *
 * Runs at kernel.request priority 18: after tenant resolution
 * ({@see \App\EventSubscriber\TenantSubscriber}, priority 20) so it can detect a
 * host that resolved to a tenant, and before tenant-facing enforcement (16) and the
 * future firewall (8).
 *
 * For a `/bo` (back-office) request it guarantees central-context entry:
 *  - if the host resolved to a tenant, the Back Office route was reached via a
 *    tenant subdomain; that is rejected with a uniform 404 (Back Office is not
 *    served as a tenant-subdomain route);
 *  - otherwise any lingering tenant context is cleared so Back Office never
 *    implicitly inherits tenant context from a tenant-facing request, session, or
 *    worker, and starts without an active tenant.
 */
final class BackOfficeBoundarySubscriber implements EventSubscriberInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly RouteBoundaryClassifier $classifier,
        private readonly TenantContext $tenantContext,
        private readonly TenantEntityManagerProvider $entityManagerProvider,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 18],
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

        if ($this->tenantContext->hasTenant()) {
            $this->logger->warning('Back Office route requested via a tenant-subdomain host; refusing.', [
                'path' => $request->getPathInfo(),
                'host' => $request->getHost(),
            ]);

            // Drop the inherited tenant context, then fail closed with a uniform,
            // non-revealing 404: Back Office is never served as a tenant route.
            $this->clearTenantContext();

            throw new NotFoundHttpException('Not Found');
        }

        // Central-context entry: ensure no tenant context is active and no tenant
        // Doctrine resources are carried into the Back Office request.
        $this->clearTenantContext();
    }

    private function clearTenantContext(): void
    {
        $this->tenantContext->reset();
        $this->entityManagerProvider->release();
    }
}
