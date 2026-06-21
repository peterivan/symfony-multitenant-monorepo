# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Read first

- **`AGENTS.md`** is the authoritative operating manual: project layout, the ADR governance rules, design discipline, and the command policy. Follow it. This file does not repeat it — it adds the architectural big picture and the exact commands.
- **`docs/adr/`** holds the accepted ADRs, which are the source of truth for architecture and boundaries. **They lead the code** — the implementation is an early skeleton and the ADRs describe the target system, so do not infer intent from code alone. Start at `docs/adr/INDEX.md`; use `docs/GLOSSARY.md` for terminology.

## Commands

All PHP/Symfony tooling runs **inside the Docker `app` container** (never on the host). Working dir in the container is `/app` (mounted from `apps/server`).

```bash
docker compose exec app composer check          # full suite: validate + lint + format:check + analyze + test
docker compose exec app composer lint           # mago lint
docker compose exec app composer format         # mago format (writes); format:check to verify only
docker compose exec app composer analyze        # phpstan (level 10) + mago analyze
docker compose exec app composer test           # pest
docker compose exec app php bin/console <cmd>    # Symfony console, e.g. debug:router, cache:clear
```

Run a single test (Pest): `docker compose exec app vendor/bin/pest --filter 'name fragment'` (or pass a path).

Use `docker compose run --rm app <cmd>` for one-offs when the stack isn't running. If `vendor/bin` is missing dev tools (phpstan/pest/mago), dev dependencies aren't installed — run `docker compose exec app composer install` (Mago downloads its binary on first run, so the container needs network access then).

App is served at `https://localhost:43443` / `http://localhost:43080`. PostgreSQL 16 service is `database`; the central DB is `central`. `ops/scripts/recreate.sh` (mounted as `recreate` in the DB container) drops all non-system databases and recreates `central` — destructive, for resetting tenancy state in dev.

## Architecture

Monorepo. The Symfony 8.1 / PHP 8.5 application is in `apps/server`. The product is a **multi-tenant SaaS platform whose defining commitment is hard tenant isolation via database-per-tenant** (one central DB for platform metadata + one PostgreSQL DB per tenant). PostgreSQL is the required, non-portable datastore (ADR-005) — use PG-specific features freely; do not add other infrastructure (Redis, brokers, etc.) without an ADR.

### Tenant resolution flow (the one fully-built subsystem)

This is the entry primitive of ADR-001 and the pattern the rest of the system builds on. Understanding it requires three files in `apps/server/src/`:

1. `EventSubscriber/TenantSubscriber.php` — on `kernel.request` (priority **20**, deliberately after routing at 32 and before the firewall at 8, so tenant context is established **before** any tenant-user authentication, per ADR-003/007). Resets context, resolves the tenant from the host, and on success stores the slug in `TenantContext` and the request attribute `tenant_slug`.
2. `Tenant/TenantResolver.php` — pure host→slug logic. Subdomain-based against `APP_TENANT_BASE_DOMAIN` (e.g. `acme.localhost` → `acme`); returns `null` for the base domain / non-matching / invalid slugs.
3. `Tenant/TenantContext.php` — request-scoped holder. Tagged `kernel.reset` and reset at the start of every request so context is **never implicitly inherited** across reused worker processes (ADR-001 invariant).

`config/services.yaml` binds `$tenantBaseDomain` from `APP_TENANT_BASE_DOMAIN` and registers the `kernel.reset` tag.

### Invariants to preserve (from the ADRs)

- **Fail closed**: tenant-scoped access must be refused when tenant context is missing/unresolved/ambiguous. Context must be resolved *before* tenant-user auth or tenant data access.
- **Two separate worlds, never bleeding**: Back Office (`/bo`, central-context only, platform-operator auth — ADR-002) vs. tenant-facing apps (inside one resolved tenant context — ADR-003). Tenant-local identities never grant Back Office or platform access.
- **Two separate identity/auth boundaries** (ADR-006/007): platform operators live in central storage; tenant users live in their tenant's storage. The same email in two tenants = two unrelated identities. No fallback between auth stores; no central "membership" model.
- Central-owned data (tenant registry, routing, provisioning) and tenant-owned data never mix. Shared caches/sessions/storage must be tenant-namespaced when holding tenant data.

### Not yet built (target per ADRs)

Doctrine ORM and the central/tenant connections, the tenant registry, runtime tenant-DB selection, per-tenant migrations, the `/bo` Back Office, authentication, and the shared Vue 3 / Vuetify / Pinia / XState / Vite frontend (ADR-004) are all designed but unimplemented. There are currently no tests beyond the Pest bootstrap.
