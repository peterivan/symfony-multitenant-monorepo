<?php

namespace App\EventSubscriber;

use App\Tenant\TenantContext;
use App\Tenant\TenantResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class TenantSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TenantResolver $tenantResolver,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->tenantContext->reset();

        $tenantSlug = $this->tenantResolver->resolveFromHost($event->getRequest()->getHost());

        if (null === $tenantSlug) {
            return;
        }

        $this->tenantContext->setTenantSlug($tenantSlug);
        $event->getRequest()->attributes->set('tenant_slug', $tenantSlug);
    }
}
