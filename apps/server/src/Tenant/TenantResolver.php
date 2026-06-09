<?php

declare(strict_types=1);

namespace App\Tenant;

final class TenantResolver
{
    public function __construct(
        private readonly string $tenantBaseDomain,
    ) {}

    #[\NoDiscard]
    public function resolveFromHost(string $host): ?string
    {
        $host = $host |> strtolower(...);
        $baseDomain = $this->tenantBaseDomain |> strtolower(...);

        if ('' === $host || $host === $baseDomain) {
            return null;
        }

        $suffix = '.' . $baseDomain;

        if (!str_ends_with($host, $suffix)) {
            return null;
        }

        $tenantSlug = substr($host, offset: 0, length: -strlen($suffix));

        if ('' === $tenantSlug) {
            return null;
        }

        if (!preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $tenantSlug)) {
            return null;
        }

        return $tenantSlug;
    }
}
