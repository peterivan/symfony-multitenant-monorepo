<?php

declare(strict_types=1);

namespace App\Central\Entity;

/**
 * Centrally managed lifecycle state of a tenant (ADR-001).
 *
 * This enum records state only; lifecycle transition operations are out of scope
 * for the central database foundation.
 */
enum TenantLifecycleState: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';
    case Deleted = 'deleted';

    /**
     * Whether a tenant in this state is eligible to serve tenant-facing traffic.
     */
    public function isServable(): bool
    {
        return self::Active === $this;
    }
}
