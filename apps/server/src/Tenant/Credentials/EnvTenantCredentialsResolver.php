<?php

declare(strict_types=1);

namespace App\Tenant\Credentials;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Dev/local credentials resolver.
 *
 * Every tenant authenticates against the shared PostgreSQL cluster with the same
 * configured credentials; the indirect reference is still required (fail closed on
 * an empty reference) so the call site is identical to a real secrets backend.
 * Replace this implementation with a Vault / Secrets Manager resolver in
 * production — nothing else in the routing layer changes.
 */
final class EnvTenantCredentialsResolver implements TenantCredentialsResolver
{
    public function __construct(
        #[Autowire('%env(TENANT_DB_USER)%')]
        private readonly string $user,
        #[Autowire('%env(TENANT_DB_PASSWORD)%')]
        #[\SensitiveParameter]
        private readonly string $password,
    ) {}

    public function resolve(string $credentialsReference): TenantDatabaseCredentials
    {
        if ('' === $credentialsReference) {
            throw new \InvalidArgumentException('Empty tenant credentials reference.');
        }

        return new TenantDatabaseCredentials($this->user, $this->password);
    }
}
