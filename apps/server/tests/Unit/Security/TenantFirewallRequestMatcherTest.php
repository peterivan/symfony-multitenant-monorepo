<?php

declare(strict_types=1);

use App\Http\Boundary\RouteBoundary;
use App\Http\Boundary\RouteBoundaryClassifier;
use App\Security\Tenant\TenantFirewallRequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

function matcher(): TenantFirewallRequestMatcher
{
    return new TenantFirewallRequestMatcher(new RouteBoundaryClassifier(test()->createMock(RouterInterface::class)));
}

function requestWithBoundary(?string $boundary): Request
{
    $request = Request::create('/whatever');

    if (null !== $boundary) {
        $request->attributes->set(RouteBoundary::DEFAULT_KEY, $boundary);
    }

    return $request;
}

it('matches only tenant-facing routes into the tenant firewall', function () {
    expect(matcher()->matches(requestWithBoundary(RouteBoundary::TenantFacing->value)))->toBeTrue();
});

it('never pulls Back Office, central, or unclassified routes into the tenant firewall', function (?string $boundary) {
    expect(matcher()->matches(requestWithBoundary($boundary)))->toBeFalse();
})->with([
    'back office' => RouteBoundary::BackOffice->value,
    'central platform' => RouteBoundary::CentralPlatform->value,
    'unclassified' => null,
]);
