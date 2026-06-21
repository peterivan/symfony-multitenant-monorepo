## 1. Install Doctrine

- [x] 1.1 Install ORM and bundle: `docker compose exec app composer require doctrine/orm doctrine/doctrine-bundle`
- [x] 1.2 Install migrations bundle: `docker compose exec app composer require doctrine/doctrine-migrations-bundle`
- [x] 1.3 Verify Doctrine commands load: `docker compose exec app php bin/console list doctrine`

## 2. Configure the central connection and EntityManager

- [x] 2.1 In `apps/server/config/packages/doctrine.yaml`, define a single connection explicitly named `central` bound to `env(DATABASE_URL)` with `server_version: 16`
- [x] 2.2 Define an EntityManager explicitly named `central` using the `central` connection
- [x] 2.3 Map only the dedicated central entity namespace/directory (e.g. `App\Central\Entity` → `src/Central/Entity`) to the `central` EntityManager; map no tenant namespace
- [x] 2.4 Verify configuration loads: `docker compose exec app php bin/console doctrine:mapping:info --em=central` (after entity exists in section 4)

## 3. Configure the central-only migrations pipeline

- [x] 3.1 Create `apps/server/config/packages/doctrine_migrations.yaml` with a central-specific migrations namespace (e.g. `App\Migrations\Central`) and directory (e.g. `%kernel.project_dir%/migrations/central`)
- [x] 3.2 Bind the migrations pipeline to the `central` EntityManager/connection
- [x] 3.3 Create the `migrations/central` directory

## 4. Define the Tenant registry entity

- [x] 4.1 Create `App\Central\Entity\Tenant` mapped to the `central` EntityManager
- [x] 4.2 Add `id` as PostgreSQL `uuid` primary key, generated at creation, with no setter (globally unique, immutable)
- [x] 4.3 Add `slug` string column with a unique constraint
- [x] 4.4 Add tenant DB location fields: `databaseHost`, `databasePort`, `databaseName`, plus a `jsonb` `connectionOptions` field for tier-specific parameters
- [x] 4.5 Add an indirect credentials reference field (no plaintext password)
- [x] 4.6 Add `lifecycleState` field constrained to at least `active`, `suspended`, `archived`, `deleted`
- [x] 4.7 Add `createdAt`/`updatedAt` `timestamptz` audit columns
- [x] 4.8 Validate schema mapping: `docker compose exec app php bin/console doctrine:schema:validate --em=central`

## 5. Generate and run the central migration

- [x] 5.1 Generate the migration: `docker compose exec app php bin/console doctrine:migrations:diff`
- [x] 5.2 Review the generated migration creates the tenant registry table with the unique slug constraint and `uuid`/`jsonb` columns
- [x] 5.3 Apply the migration: `docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction`
- [x] 5.4 Confirm tracking: `docker compose exec app php bin/console doctrine:migrations:status`

## 6. Verify boundaries

- [x] 6.1 Confirm central config/code uses the explicit `central` identifier and no unnamed default that future tenant access could share
- [x] 6.2 Confirm no tenant-owned business data fields exist on the registry entity (registry/routing/provisioning metadata only)
