<?php

declare(strict_types=1);

namespace App\BackOffice\Controller;

use App\BackOffice\TenantLifecycle\SuspendTenantOperation;
use App\Http\Boundary\RouteBoundary;
use App\Tenant\Exception\TenantNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Back Office tenant lifecycle actions (ADR-002).
 *
 * Central-context operations on the tenant registry, performed through audited
 * operation services. Back-office boundary + platform-operator authorization apply;
 * an unknown tenant fails closed with a 404.
 */
final class TenantLifecycleController
{
    #[Route(
        '/bo/tenants/{slug}/suspend',
        name: 'back_office_tenant_suspend',
        requirements: ['slug' => '[a-z0-9](?:[a-z0-9-]*[a-z0-9])?'],
        defaults: [RouteBoundary::DEFAULT_KEY => RouteBoundary::BackOffice->value],
        methods: ['POST'],
    )]
    public function suspend(string $slug, SuspendTenantOperation $operation): Response
    {
        try {
            $operation->suspend($slug);
        } catch (TenantNotFoundException) {
            return new JsonResponse(['error' => 'tenant_not_found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['slug' => $slug, 'lifecycle_state' => 'suspended']);
    }
}
