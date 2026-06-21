<?php

declare(strict_types=1);

namespace App\Tenant\Credentials;

/**
 * Resolves a tenant's indirect credentials reference (a secret name / vault path
 * stored in the registry — never a plaintext password) into usable credentials.
 *
 * Implementations back onto a secrets system; the resolver is the seam that lets a
 * real secrets backend replace the dev/local implementation without touching the
 * routing layer.
 */
interface TenantCredentialsResolver
{
    public function resolve(string $credentialsReference): TenantDatabaseCredentials;
}
