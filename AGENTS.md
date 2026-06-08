# Agent Instructions

## Project Layout

- The Symfony application lives in `apps/server`.
- The Docker Compose `app` service mounts `./apps/server` at `/app`.
- The PostgreSQL service is named `database`.

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
- Before reporting completion, run the narrowest relevant verification command available for the change, using the Docker `app` container for PHP tooling.
