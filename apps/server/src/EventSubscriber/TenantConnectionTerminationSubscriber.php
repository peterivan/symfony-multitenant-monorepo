<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Tenant\Connection\TenantEntityManagerProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Releases the tenant Doctrine connection/EntityManager at the end of each HTTP
 * request so tenant resources never leak across execution units (ADR-001).
 *
 * kernel.terminate fires after the response is sent regardless of whether the
 * request succeeded or threw, covering the failure path too. Worker reuse is
 * additionally covered by the provider's kernel.reset tag.
 */
final class TenantConnectionTerminationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TenantEntityManagerProvider $provider,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->provider->release();
    }
}
