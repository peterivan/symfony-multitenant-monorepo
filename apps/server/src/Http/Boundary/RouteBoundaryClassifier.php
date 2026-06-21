<?php

declare(strict_types=1);

namespace App\Http\Boundary;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Classifies routes and requests into a single {@see RouteBoundary} (ADR-003).
 *
 * Classification is explicit and inspectable: it reads the
 * {@see RouteBoundary::DEFAULT_KEY} route default. A route carries at most one
 * boundary, so it can never be both tenant-facing and Back Office / central.
 */
final class RouteBoundaryClassifier
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {}

    public function classifyRequest(Request $request): ?RouteBoundary
    {
        $value = $request->attributes->get(RouteBoundary::DEFAULT_KEY);

        return \is_string($value) ? RouteBoundary::tryFrom($value) : null;
    }

    public function classifyRoute(Route $route): ?RouteBoundary
    {
        $value = $route->getDefault(RouteBoundary::DEFAULT_KEY);

        return \is_string($value) ? RouteBoundary::tryFrom($value) : null;
    }

    public function requestIsTenantFacing(Request $request): bool
    {
        return RouteBoundary::TenantFacing === $this->classifyRequest($request);
    }

    /**
     * All registered route names whose boundary matches, for route-set inspection.
     *
     * @return list<string>
     */
    public function routeNamesForBoundary(RouteBoundary $boundary): array
    {
        $names = [];

        foreach ($this->router->getRouteCollection()->all() as $name => $route) {
            if ($boundary !== $this->classifyRoute($route)) {
                continue;
            }

            $names[] = $name;
        }

        return $names;
    }
}
