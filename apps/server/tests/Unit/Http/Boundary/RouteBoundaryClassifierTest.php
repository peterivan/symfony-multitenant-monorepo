<?php

declare(strict_types=1);

use App\Http\Boundary\RouteBoundary;
use App\Http\Boundary\RouteBoundaryClassifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @param array<string, Route> $routes
 */
function classifier_for(array $routes = []): RouteBoundaryClassifier
{
    $collection = new RouteCollection();

    foreach ($routes as $name => $route) {
        $collection->add($name, $route);
    }

    $router = new class($collection) implements RouterInterface {
        public function __construct(
            private RouteCollection $collection,
        ) {}

        public function getRouteCollection(): RouteCollection
        {
            return $this->collection;
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

function boundary_route(string $path, ?RouteBoundary $boundary): Route
{
    $defaults = null === $boundary ? [] : [RouteBoundary::DEFAULT_KEY => $boundary->value];

    return new Route($path, $defaults);
}

it('classifies a route by its explicit boundary default', function () {
    $classifier = classifier_for();

    expect($classifier->classifyRoute(boundary_route('/app', RouteBoundary::TenantFacing)))
        ->toBe(RouteBoundary::TenantFacing)
        ->and($classifier->classifyRoute(boundary_route('/bo', RouteBoundary::BackOffice)))
        ->toBe(RouteBoundary::BackOffice)
        ->and($classifier->classifyRoute(boundary_route('/', RouteBoundary::CentralPlatform)))
        ->toBe(RouteBoundary::CentralPlatform);
});

it('returns null for an unclassified route', function () {
    expect(classifier_for()->classifyRoute(boundary_route('/whatever', boundary: null)))->toBeNull();
});

it('classifies a request from its resolved boundary attribute', function () {
    $request = Request::create('/app');
    $request->attributes->set(RouteBoundary::DEFAULT_KEY, RouteBoundary::TenantFacing->value);

    $classifier = classifier_for();

    expect($classifier->classifyRequest($request))
        ->toBe(RouteBoundary::TenantFacing)
        ->and($classifier->requestIsTenantFacing($request))
        ->toBeTrue();
});

it('does not treat an unmarked request as tenant-facing', function () {
    expect(classifier_for()->requestIsTenantFacing(Request::create('/')))->toBeFalse();
});

it('lists route names per boundary and keeps tenant-facing routes out of the back-office set', function () {
    $classifier = classifier_for([
        'home' => boundary_route('/', RouteBoundary::CentralPlatform),
        'tenant_area' => boundary_route('/app', RouteBoundary::TenantFacing),
        'bo_dashboard' => boundary_route('/bo', RouteBoundary::BackOffice),
    ]);

    expect($classifier->routeNamesForBoundary(RouteBoundary::TenantFacing))
        ->toBe(['tenant_area'])
        ->and($classifier->routeNamesForBoundary(RouteBoundary::BackOffice))
        ->toBe(['bo_dashboard'])
        ->and($classifier->routeNamesForBoundary(RouteBoundary::TenantFacing))
        ->not->toContain('bo_dashboard');
});

it('gives every route at most one boundary classification', function () {
    $route = boundary_route('/app', RouteBoundary::TenantFacing);
    $classifier = classifier_for();

    $matches = array_filter(
        RouteBoundary::cases(),
        static fn(RouteBoundary $b): bool => $b === $classifier->classifyRoute($route),
    );

    expect($matches)->toHaveCount(1);
});
