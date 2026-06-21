<?php

declare(strict_types=1);

namespace App\BackOffice\Controller;

use App\Central\Repository\TenantRepository;
use App\Http\Boundary\RouteBoundary;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Back Office dashboard (ADR-002).
 *
 * A central-context operation: it reads the central tenant registry and never
 * establishes tenant context. The `_boundary` route default classifies it as
 * back-office, so the Back Office boundary + platform-operator authorization
 * subscribers govern access; it is unreachable via a tenant subdomain.
 */
final class BackOfficeDashboardController
{
    #[Route(
        '/bo',
        name: 'back_office_dashboard',
        defaults: [RouteBoundary::DEFAULT_KEY => RouteBoundary::BackOffice->value],
        methods: ['GET'],
    )]
    public function __invoke(TenantRepository $tenantRepository): Response
    {
        $tenants = array_map(
            static fn($tenant): array => [
                'slug' => $tenant->getSlug(),
                'lifecycle_state' => $tenant->getLifecycleState()->value,
            ],
            $tenantRepository->findBy([], ['slug' => 'ASC']),
        );

        return new JsonResponse(['tenants' => $tenants]);
    }
}
