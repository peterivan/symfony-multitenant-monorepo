<?php

declare(strict_types=1);

namespace App\Tenant\Exception;

/**
 * Base type for all fail-closed tenant connectivity errors (ADR-001).
 *
 * Any failure to prove tenant context or to resolve a tenant database is raised
 * as one of these and never resolved by falling back to the central database, a
 * default tenant, or stale context.
 */
abstract class TenantConnectivityException extends \RuntimeException {}
