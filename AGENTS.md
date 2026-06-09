# Agent Instructions

## Project Layout

- Treat this repository as a monorepo.
- The Symfony application lives in `apps/server`.
- The Docker Compose `app` service mounts `./apps/server` at `/app`.
- The PostgreSQL service is named `database`.
- Do not assume all code belongs to the Symfony application. Before making changes, determine which application, package, or documentation area owns the requested change.
- Do not move code between applications or packages unless explicitly requested.

## Architecture Documents

- Accepted ADRs in `docs/adr/` define governing architectural boundaries. Use `docs/adr/INDEX.md` to find the current ADR set.
- The project-wide glossary is `docs/GLOSSARY.md`. Use it for project terminology before inventing or redefining terms in code, docs, reviews, or implementation discussion.
- Before proposing or implementing changes to code, tests, configuration, infrastructure, or documentation that touch tenancy, tenant context, Back Office, tenant-facing applications, frontend foundation, PostgreSQL usage, identity, authentication, authorization, data ownership, or platform operations, read the relevant accepted ADRs and keep the work inside their boundaries.
- Do not infer architectural intent solely from existing code. Accepted ADRs take precedence over implementation details that appear to conflict with them.
- If a requested change conflicts with an accepted ADR, do not silently implement around it. Call out the conflict and either keep the change within the existing boundary or propose a superseding ADR when the boundary itself must change.
- Do not treat implementation code as permission to violate ADR invariants. ADRs are the source of truth for architectural ownership and boundary rules until superseded.

## Design Discipline

- Do not introduce new architectural concepts, boundaries, identity models, tenancy models, deployment models, framework layers, or major abstractions unless required by an accepted ADR or explicitly requested.
- Prefer the simplest implementation that satisfies existing ADR requirements.
- PostgreSQL is the primary data store. Do not introduce additional infrastructure dependencies such as Redis, Kafka, Elasticsearch, RabbitMQ, message brokers, or external services unless required by an accepted ADR or explicitly requested.
- When required information is missing, state assumptions explicitly. Prefer requesting clarification over inventing project requirements, architectural constraints, or business rules.

## Command Policy

- Run all PHP-specific tooling inside the Docker `app` container. Do not run PHP, Composer, Symfony Console, PHPUnit, PHPStan, Mago, or other PHP/Symfony tools directly on the host.
- When the stack is already running, use:

```bash
docker compose exec app <command>
```

- For one-off commands when the stack may not be running, use:

```bash
docker compose run --rm app <command>
```

- Because `/app` is the container working directory, run project commands from there. Examples:

```bash
docker compose exec app composer install
docker compose exec app composer validate
docker compose exec app php bin/console about
docker compose exec app php bin/console cache:clear
```

## Working Guidelines

- Prefer repository-local configuration and scripts over ad hoc commands.
- Keep changes scoped to the requested feature or fix.
- If a change modifies behavior described by project documentation, update the affected documentation as part of the same change.
- Do not knowingly leave implementation and documentation inconsistent.
- Before reporting completion, run the narrowest relevant verification command available for the change, using the Docker `app` container for PHP tooling.
