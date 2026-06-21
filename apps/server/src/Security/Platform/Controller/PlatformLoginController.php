<?php

declare(strict_types=1);

namespace App\Security\Platform\Controller;

use App\Http\Boundary\RouteBoundary;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Check path for platform (Back Office) authentication (ADR-007).
 *
 * Credentials POSTed here are handled by the platform firewall's json_login
 * authenticator against the central platform-owned identity store. This controller
 * body is a defensive fallback only; the authenticator returns the real response.
 */
final class PlatformLoginController
{
    #[Route(
        '/bo/login',
        name: 'back_office_login',
        defaults: [RouteBoundary::DEFAULT_KEY => RouteBoundary::BackOffice->value],
        methods: ['POST'],
    )]
    public function __invoke(): Response
    {
        return new JsonResponse(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
    }
}
