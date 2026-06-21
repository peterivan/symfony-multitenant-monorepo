<?php

declare(strict_types=1);

namespace App\Tenant\Connection;

use App\Central\Entity\Tenant;
use App\Tenant\Credentials\TenantCredentialsResolver;
use App\Tenant\Exception\IncompleteTenantLocationException;

/**
 * Builds Doctrine DBAL connection parameters for a tenant from its registry
 * location metadata plus credentials resolved from the indirect reference.
 *
 * Fails closed (never guesses a target) when required location metadata is
 * missing. PostgreSQL only (ADR-005).
 */
final class TenantConnectionParametersFactory
{
    public function __construct(
        private readonly TenantCredentialsResolver $credentialsResolver,
    ) {}

    /**
     * @return array<string, scalar|null>
     */
    public function create(Tenant $tenant): array
    {
        $location = $tenant->getDatabaseLocation();
        $slug = $tenant->getSlug();

        if ('' === $location->getHost()) {
            throw new IncompleteTenantLocationException($slug, 'missing database host');
        }

        if ('' === $location->getName()) {
            throw new IncompleteTenantLocationException($slug, 'missing database name');
        }

        if ('' === $location->getCredentialsReference()) {
            throw new IncompleteTenantLocationException($slug, 'missing credentials reference');
        }

        $credentials = $this->credentialsResolver->resolve($location->getCredentialsReference());

        // Tier-specific options first; authoritative connection target last so it
        // can never be overridden by registry-stored options.
        $params = $location->getConnectionOptions();
        $params['driver'] = 'pdo_pgsql';
        $params['host'] = $location->getHost();
        $params['port'] = $location->getPort();
        $params['dbname'] = $location->getName();
        $params['user'] = $credentials->user;
        $params['password'] = $credentials->password;
        $params['charset'] = 'utf8';
        // Avoid an extra round-trip to auto-detect the server version.
        $params['serverVersion'] = '16';

        return $params;
    }
}
