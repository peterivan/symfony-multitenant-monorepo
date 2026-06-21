<?php

declare(strict_types=1);

namespace App\Central\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tenant database location metadata (ADR-001).
 *
 * Embedded in the {@see Tenant} registry entry. Stores enough information to open
 * the tenant's PostgreSQL database across the shared, dedicated-DB, and
 * dedicated-deployment isolation tiers — more than just the database name.
 * Credentials are referenced indirectly and never stored as plaintext.
 */
#[ORM\Embeddable]
final class TenantDatabaseLocation
{
    #[ORM\Column(length: 255)]
    private string $host;

    #[ORM\Column]
    private int $port;

    #[ORM\Column(length: 63)]
    private string $name;

    /**
     * Indirect reference to the tenant database credentials (e.g. a secret name or
     * vault path). Never a plaintext password.
     */
    #[ORM\Column(length: 255)]
    private string $credentialsReference;

    /**
     * Additional PostgreSQL connection parameters for tier-specific or
     * dedicated-instance tenants (sslmode, options, application_name, ...).
     *
     * @var array<string, scalar|null>
     */
    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true])]
    private array $connectionOptions;

    /**
     * @param array<string, scalar|null> $connectionOptions
     */
    public function __construct(
        string $host,
        string $name,
        string $credentialsReference,
        int $port = 5432,
        array $connectionOptions = [],
    ) {
        $this->host = $host;
        $this->name = $name;
        $this->credentialsReference = $credentialsReference;
        $this->port = $port;
        $this->connectionOptions = $connectionOptions;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCredentialsReference(): string
    {
        return $this->credentialsReference;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getConnectionOptions(): array
    {
        return $this->connectionOptions;
    }
}
