<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Boundary\RouteBoundary;
use App\Tenant\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    // Central-platform route: reachable without a tenant context (it also reports
    // the resolved tenant when present), so it is not subject to tenant-facing
    // boundary enforcement.
    #[Route(
        '/',
        name: 'home',
        defaults: [RouteBoundary::DEFAULT_KEY => RouteBoundary::CentralPlatform->value],
        methods: ['GET'],
    )]
    public function __invoke(TenantContext $tenantContext, RequestStack $requestStack): Response
    {
        $tenantSlug = $tenantContext->getTenantSlug() ?? 'none';
        $host = $requestStack->getCurrentRequest()?->getHost() ?? 'unknown';

        return new Response(sprintf("host: %s\ntenant: %s\n", $host, $tenantSlug));
    }
}
