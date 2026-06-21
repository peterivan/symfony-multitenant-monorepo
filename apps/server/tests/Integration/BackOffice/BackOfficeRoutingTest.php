<?php

declare(strict_types=1);

use App\Http\Boundary\RouteBoundary;
use App\Http\Boundary\RouteBoundaryClassifier;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

uses(KernelTestCase::class);

beforeEach(function () {
    self::bootKernel();
    // `router` is a public service; build the classifier over it directly so this
    // test needs neither the test service container nor the test environment.
    $this->router = self::$kernel->getContainer()->get('router');
    $this->classifier = new RouteBoundaryClassifier($this->router);
});

it('exposes the Back Office dashboard under /bo', function () {
    /** @var RouterInterface $router */
    $router = $this->router;
    $route = $router->getRouteCollection()->get('back_office_dashboard');

    expect($route)->not->toBeNull()->and($route->getPath())->toBe('/bo');
});

it('classifies every /bo route as back-office and never tenant-facing', function () {
    /** @var RouterInterface $router */
    $router = $this->router;
    /** @var RouteBoundaryClassifier $classifier */
    $classifier = $this->classifier;

    foreach ($router->getRouteCollection()->all() as $name => $route) {
        if (!str_starts_with($route->getPath(), '/bo')) {
            continue;
        }

        expect($classifier->classifyRoute($route))
            ->toBe(RouteBoundary::BackOffice, sprintf('Route "%s" under /bo must be back-office.', $name));
    }
});

it('registers no tenant-facing route under /bo', function () {
    /** @var RouterInterface $router */
    $router = $this->router;
    /** @var RouteBoundaryClassifier $classifier */
    $classifier = $this->classifier;

    foreach ($classifier->routeNamesForBoundary(RouteBoundary::TenantFacing) as $name) {
        $route = $router->getRouteCollection()->get($name);

        expect($route)->not->toBeNull()->and(str_starts_with($route->getPath(), '/bo'))->toBeFalse();
    }
});

it('binds no Back Office route to a tenant-subdomain host pattern', function () {
    /** @var RouterInterface $router */
    $router = $this->router;
    /** @var RouteBoundaryClassifier $classifier */
    $classifier = $this->classifier;

    foreach ($classifier->routeNamesForBoundary(RouteBoundary::BackOffice) as $name) {
        $route = $router->getRouteCollection()->get($name);

        // No host requirement: central-only; tenant-subdomain reachability is rejected
        // at runtime, never registered as a tenant route.
        expect($route->getHost())->toBe('');
    }
});
