<?php

namespace App\Tenant;

final class TenantResolver
{
    public function __construct(
        private readonly string $tenantBaseDomain,
    ) {
    }

    public function resolveFromHost(string $host): ?string
    {
        $host = strtolower($host);
        $baseDomain = strtolower($this->tenantBaseDomain);

        if ('' === $host || $host === $baseDomain) {
            return null;
        }

        $suffix = '.'.$baseDomain;

        if (!str_ends_with($host, $suffix)) {
            return null;
        }

        $tenantSlug = substr($host, 0, -strlen($suffix));

        if ('' === $tenantSlug) {
            return null;
        }

        if (!preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $tenantSlug)) {
            return null;
        }

        return $tenantSlug;
    }
}
