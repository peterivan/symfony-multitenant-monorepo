<?php

namespace App\Tenant;

use Symfony\Contracts\Service\ResetInterface;

final class TenantContext implements ResetInterface
{
    private ?string $tenantSlug = null;

    public function setTenantSlug(string $tenantSlug): void
    {
        $this->tenantSlug = $tenantSlug;
    }

    public function getTenantSlug(): ?string
    {
        return $this->tenantSlug;
    }

    public function hasTenant(): bool
    {
        return null !== $this->tenantSlug;
    }

    public function reset(): void
    {
        $this->tenantSlug = null;
    }
}
