<?php

declare(strict_types=1);

namespace App\Security\Tenant\Controller;

use App\Http\Boundary\RouteBoundary;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Check path for tenant authentication (ADR-007).
 *
 * Tenant-facing by boundary, so the tenant route boundary fails closed (404)
 * before this is reached when no servable tenant context is resolved — enforcing
 * tenant-context-first login. Credentials POSTed here are handled by the tenant
 * firewall's json_login authenticator against the identity store selected by the
 * resolved tenant context. This controller body is a defensive fallback only.
 */
final class TenantLoginController
{
    #[Route(
        '/app/login',
        name: 'tenant_login',
        defaults: [RouteBoundary::DEFAULT_KEY => RouteBoundary::TenantFacing->value],
        methods: ['POST'],
    )]
    public function __invoke(): Response
    {
        return new JsonResponse(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
    }
}
