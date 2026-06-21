<?php

declare(strict_types=1);

namespace App\Security\Tenant;

use App\Http\Boundary\RouteBoundary;
use App\Http\Boundary\RouteBoundaryClassifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * Maps the tenant firewall to tenant-facing routes by their explicit boundary
 * classification (ADR-003 / ADR-007), not by a path prefix.
 *
 * Runs at firewall-matching time (after routing), so the route boundary default is
 * already on the request. Only tenant-facing routes enter the tenant firewall;
 * Back Office (`/bo`) and central-platform routes never do.
 */
final class TenantFirewallRequestMatcher implements RequestMatcherInterface
{
    public function __construct(
        private readonly RouteBoundaryClassifier $classifier,
    ) {}

    public function matches(Request $request): bool
    {
        return RouteBoundary::TenantFacing === $this->classifier->classifyRequest($request);
    }
}
