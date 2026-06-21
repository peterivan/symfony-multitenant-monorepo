<?php

declare(strict_types=1);

namespace App\Central\Entity;

/**
 * Outcome of an audited platform operation (ADR-002).
 *
 * Both successful and failed audited operations are recorded; the audit entry is
 * written on every path.
 */
enum AuditOutcome: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
