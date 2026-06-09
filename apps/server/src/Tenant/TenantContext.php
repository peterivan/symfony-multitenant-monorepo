<?php

declare(strict_types=1);

namespace App\Tenant;

use Symfony\Contracts\Service\ResetInterface;

final class TenantContext implements ResetInterface
{
    private ?string $tenantSlug = null;

    public function setTenantSlug(string $tenantSlug): void
    {
        $this->tenantSlug = $tenantSlug;
    }

    #[\NoDiscard]
    public function getTenantSlug(): ?string
    {
        return $this->tenantSlug;
    }

    #[\NoDiscard]
    public function hasTenant(): bool
    {
        return null !== $this->tenantSlug;
    }

    public function reset(): void
    {
        $this->tenantSlug = null;
    }
}
