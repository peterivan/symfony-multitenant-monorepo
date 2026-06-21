<?php

declare(strict_types=1);

use App\BackOffice\Http\BackOfficeBoundarySubscriber;
use App\Central\Entity\Tenant;
use App\Http\Boundary\RouteBoundary;
use App\Http\Boundary\RouteBoundaryClassifier;
use App\Tenant\Connection\TenantConnectionFactory;
use App\Tenant\Connection\TenantEntityManagerProvider;
use App\Tenant\Registry\TenantRegistry;
use App\Tenant\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

function bo_classifier(): RouteBoundaryClassifier
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

        public function setContext(RequestContext $context): void {}

        public function getContext(): RequestContext
        {
            return new RequestContext();
        }
    };

    return new RouteBoundaryClassifier($router);
}

function bo_provider(TenantContext $context): TenantEntityManagerProvider
{
    $registry = new class implements TenantRegistry {
        public function findBySlug(string $slug): ?Tenant
        {
            return null;
        }
    };

    $factory = new class implements TenantConnectionFactory {
        public function createEntityManager(Tenant $tenant): EntityManagerInterface
        {
            throw new \LogicException('Should not open a tenant connection in a Back Office request.');
        }
    };

    return new TenantEntityManagerProvider($context, $registry, $factory);
}

function bo_event(
    TenantContext $context,
    RouteBoundary $boundary,
    int $type = HttpKernelInterface::MAIN_REQUEST,
): RequestEvent {
    $request = Request::create('/bo', server: ['HTTP_HOST' => 'acme.example.com']);
    $request->attributes->set(RouteBoundary::DEFAULT_KEY, $boundary->value);

    $kernel = new class implements HttpKernelInterface {
        public function handle(
            Request $request,
            int $type = self::MAIN_REQUEST,
            bool $catch = true,
        ): \Symfony\Component\HttpFoundation\Response {
            return new \Symfony\Component\HttpFoundation\Response();
        }
    };

    return new RequestEvent($kernel, $request, $type);
}

function bo_subscriber(TenantContext $context): BackOfficeBoundarySubscriber
{
    return new BackOfficeBoundarySubscriber(bo_classifier(), $context, bo_provider($context));
}

it('runs after tenant resolution and before tenant-facing enforcement', function () {
    $priority = BackOfficeBoundarySubscriber::getSubscribedEvents()[KernelEvents::REQUEST][1];

    expect($priority)->toBe(18)->and(18)->toBeLessThan(20)->and(18)->toBeGreaterThan(16);
});

it('ignores non-back-office routes and leaves context untouched', function () {
    $context = new TenantContext();
    $context->setTenantSlug('acme');

    bo_subscriber($context)->onKernelRequest(bo_event($context, RouteBoundary::CentralPlatform));

    expect($context->getTenantSlug())->toBe('acme');
});

it('ignores sub-requests', function () {
    $context = new TenantContext();

    bo_subscriber($context)->onKernelRequest(bo_event(
        $context,
        RouteBoundary::BackOffice,
        HttpKernelInterface::SUB_REQUEST,
    ));

    expect($context->hasTenant())->toBeFalse();
});

it('enters in central context without an active tenant when the host did not resolve a tenant', function () {
    $context = new TenantContext();

    bo_subscriber($context)->onKernelRequest(bo_event($context, RouteBoundary::BackOffice));

    expect($context->hasTenant())->toBeFalse();
});

it('clears any inherited tenant context so Back Office never inherits it', function () {
    // A tenant context present on a back-office request means it was reached via a
    // tenant subdomain (or carried over). Either way it must not survive into /bo.
    $context = new TenantContext();
    $context->setTenantSlug('acme');

    expect(fn() => bo_subscriber($context)->onKernelRequest(bo_event($context, RouteBoundary::BackOffice)))
        ->toThrow(NotFoundHttpException::class)
        ->and($context->hasTenant())
        ->toBeFalse();
});

it('fails closed with a 404 when a Back Office route is reached via a tenant subdomain', function () {
    $context = new TenantContext();
    $context->setTenantSlug('acme');

    bo_subscriber($context)->onKernelRequest(bo_event($context, RouteBoundary::BackOffice));
})->throws(NotFoundHttpException::class);
