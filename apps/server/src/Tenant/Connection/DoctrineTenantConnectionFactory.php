<?php

declare(strict_types=1);

namespace App\Tenant\Connection;

use App\Central\Entity\Tenant;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Builds a tenant EntityManager at runtime from registry metadata.
 *
 * Reuses the configured `tenant` EntityManager purely as a template for Doctrine
 * configuration (mapping/proxy/naming) and DBAL middlewares; the actual connection
 * is constructed fresh from the per-tenant parameters so each tenant is bound to
 * its own database. The template connection itself is never opened.
 */
final class DoctrineTenantConnectionFactory implements TenantConnectionFactory
{
    public function __construct(
        private readonly TenantConnectionParametersFactory $parametersFactory,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        private readonly EntityManagerInterface $templateEntityManager,
    ) {}

    public function createEntityManager(Tenant $tenant): EntityManagerInterface
    {
        $params = $this->parametersFactory->create($tenant);

        $dbalConfiguration = $this->templateEntityManager->getConnection()->getConfiguration();
        $ormConfiguration = $this->templateEntityManager->getConfiguration();

        // $params is built from trusted central registry metadata; its dynamic
        // shape cannot be expressed as DBAL's strict Params array shape.
        // @mago-expect analysis:invalid-argument
        // @phpstan-ignore argument.type
        $connection = DriverManager::getConnection($params, $dbalConfiguration);

        return new EntityManager($connection, $ormConfiguration);
    }
}
