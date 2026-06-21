<?php

declare(strict_types=1);

use App\Central\Entity\Tenant;
use App\Central\Entity\TenantDatabaseLocation;
use App\Tenant\Connection\TenantConnectionParametersFactory;
use App\Tenant\Credentials\TenantCredentialsResolver;
use App\Tenant\Credentials\TenantDatabaseCredentials;
use App\Tenant\Exception\IncompleteTenantLocationException;

function fixed_credentials_resolver(string $user, #[\SensitiveParameter] string $secret): TenantCredentialsResolver
{
    return new class($user, $secret) implements TenantCredentialsResolver {
        public function __construct(
            private string $user,
            #[\SensitiveParameter]
            private string $secret,
        ) {}

        public function resolve(string $credentialsReference): TenantDatabaseCredentials
        {
            return new TenantDatabaseCredentials($this->user, $this->secret);
        }
    };
}

it('builds PostgreSQL DBAL params from registry location metadata and resolved credentials', function () {
    $secret = uniqid('pw_', more_entropy: true);
    $factory = new TenantConnectionParametersFactory(fixed_credentials_resolver('acme_user', $secret));
    $tenant = new Tenant('acme', new TenantDatabaseLocation('tenant-db', 'tenant_acme', 'secret://acme', 6432));

    expect($factory->create($tenant))->toMatchArray([
        'driver' => 'pdo_pgsql',
        'host' => 'tenant-db',
        'port' => 6432,
        'dbname' => 'tenant_acme',
        'user' => 'acme_user',
        'password' => $secret,
        'charset' => 'utf8',
        'serverVersion' => '16',
    ]);
});

it('merges tier-specific options but never lets them override the authoritative target', function () {
    $factory = new TenantConnectionParametersFactory(fixed_credentials_resolver('tenant_user', uniqid()));
    $location = new TenantDatabaseLocation('real-host', 'tenant_acme', 'secret://acme', 5432, [
        'sslmode' => 'require',
        'host' => 'spoofed-host',
        'dbname' => 'spoofed_db',
    ]);

    $params = $factory->create(new Tenant('acme', $location));

    expect($params['sslmode'])
        ->toBe('require')
        ->and($params['host'])
        ->toBe('real-host')
        ->and($params['dbname'])
        ->toBe('tenant_acme');
});

it('fails closed when the database host is missing', function () {
    $factory = new TenantConnectionParametersFactory(fixed_credentials_resolver('tenant_user', uniqid()));
    $factory->create(new Tenant('acme', new TenantDatabaseLocation('', 'tenant_acme', 'secret://acme')));
})->throws(IncompleteTenantLocationException::class);

it('fails closed when the database name is missing', function () {
    $factory = new TenantConnectionParametersFactory(fixed_credentials_resolver('tenant_user', uniqid()));
    $factory->create(new Tenant('acme', new TenantDatabaseLocation('tenant-db', '', 'secret://acme')));
})->throws(IncompleteTenantLocationException::class);

it('fails closed when the credentials reference is empty', function () {
    $factory = new TenantConnectionParametersFactory(fixed_credentials_resolver('tenant_user', uniqid()));
    $factory->create(new Tenant('acme', new TenantDatabaseLocation('tenant-db', 'tenant_acme', '')));
})->throws(IncompleteTenantLocationException::class);
