<?php

declare(strict_types=1);

namespace App\Messenger\Tenant;

/**
 * Marks a message that must be handled inside a tenant context (ADR-001).
 *
 * A tenant-scoped message MUST carry a {@see TenantStamp}. When consumed without
 * one, handling fails closed rather than running under an implicit or default
 * tenant.
 */
interface TenantScopedMessage {}
