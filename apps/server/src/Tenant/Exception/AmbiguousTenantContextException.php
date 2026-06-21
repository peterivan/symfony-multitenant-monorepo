<?php

declare(strict_types=1);

namespace App\Tenant\Exception;

/**
 * Raised when more than one conflicting tenant is signalled for a single execution
 * unit. Fails closed: no single tenant is silently selected (ADR-003).
 */
final class AmbiguousTenantContextException extends TenantConnectivityException
{
    /**
     * @param list<string> $candidates
     */
    public function __construct(array $candidates)
    {
        parent::__construct(\sprintf(
            'Ambiguous tenant context: %d conflicting tenants signalled (%s): refusing to select one (fail closed).',
            \count($candidates),
            implode(', ', $candidates),
        ));
    }
}
