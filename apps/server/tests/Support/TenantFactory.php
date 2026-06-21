<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Central\Entity\Tenant;
use App\Central\Entity\TenantDatabaseLocation;

final class TenantFactory
{
    public static function make(string $slug): Tenant
    {
        return new Tenant(
            $slug,
            new TenantDatabaseLocation('tenant-db', 'tenant_' . $slug, 'secret://tenants/' . $slug),
        );
    }
}
