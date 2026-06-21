<?php

declare(strict_types=1);

namespace App\Controller\Tenant;

use App\Http\Boundary\RouteBoundary;
use App\Tenant\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sample tenant-facing endpoint demonstrating the tenant-facing route boundary
 * (ADR-003).
 *
 * The `_boundary` route default classifies this route as tenant-facing, so
 * {@see \App\Http\Boundary\TenantBoundaryEnforcementSubscriber} requires a
 * resolved, active, establishable tenant context before this controller runs.
 * Reached without one, the request fails closed with a 404.
 */
final class TenantAreaController extends AbstractController
{
    #[Route(
        '/app',
        name: 'tenant_area',
        defaults: [RouteBoundary::DEFAULT_KEY => RouteBoundary::TenantFacing->value],
        methods: ['GET'],
    )]
    public function __invoke(TenantContext $tenantContext): Response
    {
        // Enforcement has already guaranteed an active tenant context here.
        return new Response(\sprintf("tenant area\ntenant: %s\n", (string) $tenantContext->getTenantSlug()));
    }
}
