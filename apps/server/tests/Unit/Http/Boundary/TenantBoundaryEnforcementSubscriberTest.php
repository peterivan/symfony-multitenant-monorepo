<?php

declare(strict_types=1);

use App\Central\Entity\Tenant;
use App\Central\Entity\TenantLifecycleState;
use App\Http\Boundary\RouteBoundary;
use App\Http\Boundary\RouteBoundaryClassifier;
use App\Http\Boundary\TenantBoundaryEnforcementSubscriber;
use App\Tenant\Boundary\TenantBoundaryGuard;
use App\Tenant\Connection\TenantConnectionFactory;
use App\Tenant\Connection\TenantEntityManagerProvider;
use App\Tenant\Registry\TenantRegistry;
use App\Tenant\TenantContext;
use App\Tests\Support\TenantFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

function enf_classifier(): RouteBoundaryClassifier
{
    $router = new class implements RouterInterface {
        public function getRouteCollection(): RouteCollection
        {
            return new RouteCollection();
        }

        public function match(string $pathinfo): array
        {
            return [];
        }

        public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
        {
            return '/';
        }

        public function setContext(\Symfony\Component\Routing\RequestContext $context): void {}

        public function getContext(): \Symfony\Component\Routing\RequestContext
        {
            return new \Symfony\Component\Routing\RequestContext();
        }
    };

    return new RouteBoundaryClassifier($router);
}

/**
 * @param array<string, Tenant> $tenants
 */
function enf_guard(
    ?string $contextSlug,
    array $tenants,
    ?EntityManagerInterface $entityManager = null,
): TenantBoundaryGuard {
    $context = new TenantContext();

    if (null !== $contextSlug) {
        $context->setTenantSlug($contextSlug);
    }

    $registry = new class($tenants) implements TenantRegistry {
        /** @param array<string, Tenant> $tenants */
        public function __construct(
            private array $tenants,
        ) {}

        public function findBySlug(string $slug): ?Tenant
        {
            return $this->tenants[$slug] ?? null;
        }
    };

    $factory = new class($entityManager) implements TenantConnectionFactory {
        public function __construct(
            private ?EntityManagerInterface $entityManager,
        ) {}

        public function createEntityManager(Tenant $tenant): EntityManagerInterface
        {
            return $this->entityManager ?? throw new \LogicException('No entity manager configured.');
        }
    };

    return new TenantBoundaryGuard($context, new TenantEntityManagerProvider($context, $registry, $factory));
}

function enf_request(RouteBoundary $boundary): Request
{
    $request = Request::create('/app');
    $request->attributes->set(RouteBoundary::DEFAULT_KEY, $boundary->value);

    return $request;
}

function enf_event(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
{
    return new RequestEvent(
        new class implements HttpKernelInterface {
            public function handle(
                Request $request,
                int $type = self::MAIN_REQUEST,
                bool $catch = true,
            ): \Symfony\Component\HttpFoundation\Response {
                return new \Symfony\Component\HttpFoundation\Response();
            }
        },
        $request,
        $type,
    );
}

it('enforces after resolution and before the firewall', function () {
    $events = TenantBoundaryEnforcementSubscriber::getSubscribedEvents();

    expect($events[KernelEvents::REQUEST][1])
        ->toBe(16) // after TenantSubscriber (20), before firewall (8)
        ->and(16)
        ->toBeLessThan(20)
        ->and(16)
        ->toBeGreaterThan(8);
});

it('ignores non-tenant-facing routes and never invokes the guard', function () {
    // guard would throw (no context) if called; central route must not call it
    $subscriber = new TenantBoundaryEnforcementSubscriber(enf_classifier(), enf_guard(contextSlug: null, tenants: []));

    $subscriber->onKernelRequest(enf_event(enf_request(RouteBoundary::CentralPlatform)));

    expect(true)->toBeTrue();
});

it('ignores sub-requests', function () {
    $subscriber = new TenantBoundaryEnforcementSubscriber(enf_classifier(), enf_guard(contextSlug: null, tenants: []));

    $subscriber->onKernelRequest(enf_event(enf_request(RouteBoundary::TenantFacing), HttpKernelInterface::SUB_REQUEST));

    expect(true)->toBeTrue();
});

it('lets an established tenant-facing request proceed', function () {
    $em = $this->createMock(EntityManagerInterface::class);
    $subscriber = new TenantBoundaryEnforcementSubscriber(enf_classifier(), enf_guard(
        'acme',
        ['acme' => TenantFactory::make('acme')],
        $em,
    ));

    $subscriber->onKernelRequest(enf_event(enf_request(RouteBoundary::TenantFacing)));

    expect(true)->toBeTrue();
});

it('fails closed with a uniform 404 when context is missing', function () {
    $subscriber = new TenantBoundaryEnforcementSubscriber(enf_classifier(), enf_guard(contextSlug: null, tenants: []));

    $subscriber->onKernelRequest(enf_event(enf_request(RouteBoundary::TenantFacing)));
})->throws(NotFoundHttpException::class);

it('fails closed with the same 404 for an inactive tenant (no cause leaked)', function () {
    $suspended = TenantFactory::make('acme');
    $suspended->changeLifecycleState(TenantLifecycleState::Suspended);

    $subscriber = new TenantBoundaryEnforcementSubscriber(enf_classifier(), enf_guard('acme', ['acme' => $suspended]));

    $subscriber->onKernelRequest(enf_event(enf_request(RouteBoundary::TenantFacing)));
})->throws(NotFoundHttpException::class, 'Not Found');

it('fails closed with a 404 for an unknown tenant', function () {
    $subscriber = new TenantBoundaryEnforcementSubscriber(enf_classifier(), enf_guard('ghost', []));

    $subscriber->onKernelRequest(enf_event(enf_request(RouteBoundary::TenantFacing)));
})->throws(NotFoundHttpException::class);
