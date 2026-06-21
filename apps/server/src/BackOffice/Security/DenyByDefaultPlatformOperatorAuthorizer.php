<?php

declare(strict_types=1);

namespace App\BackOffice\Security;

/**
 * Default platform-operator authorizer: denies everything (ADR-002, fail closed).
 *
 * Placeholder seam until the authentication change (ADR-007) wires real
 * platform-operator authentication in central storage. It intentionally consults
 * nothing tenant-local: there is no input by which a tenant-local identity could
 * grant Back Office access.
 */
final class DenyByDefaultPlatformOperatorAuthorizer implements PlatformOperatorAuthorizer
{
    public function isPlatformOperator(): bool
    {
        return false;
    }

    public function currentOperatorReference(): ?string
    {
        return null;
    }
}
