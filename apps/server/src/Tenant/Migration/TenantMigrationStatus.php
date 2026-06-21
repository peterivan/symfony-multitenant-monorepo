<?php

declare(strict_types=1);

namespace App\Tenant\Migration;

/**
 * Outcome of a single tenant's migration attempt within an orchestrated run.
 */
enum TenantMigrationStatus: string
{
    case Migrated = 'migrated';
    case AlreadyUpToDate = 'already-up-to-date';
    case SkippedNotNew = 'skipped-not-new';
    case Failed = 'failed';
}
