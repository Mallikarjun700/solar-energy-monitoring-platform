# Configuration Reference

This document describes configuration categories used by the platform. **Do not commit secrets or production credentials to the repository.**

## Configuration groups

| Area | Purpose |
|---|---|
| Laravel application | Runtime environment, URL, encryption and logging |
| MySQL | Primary application data |
| Telemetry PostgreSQL | Telemetry persistence, hosted by Supabase in the current deployment model |
| Redis | Queue/cache support |
| Angular | Backend API endpoint |
| Render | Automated application deployment |
| AWS | Optional ECS/Fargate deployment path |
| Terraform | AWS infrastructure variables |

## Laravel application

Typical Laravel variables include:

```dotenv
APP_ENV=
APP_KEY=
APP_DEBUG=
APP_URL=
LOG_CHANNEL=
```

Generate the application key locally with:

```bash
php artisan key:generate
```

Production secrets belong in the deployment platform's secret/environment-variable store rather than source control.

## Primary MySQL database

The application database uses the standard Laravel MySQL connection:

```dotenv
DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=solar_energy
DB_USERNAME=
DB_PASSWORD=
```

The exact values are environment-specific.

## Telemetry PostgreSQL

Telemetry uses a separate PostgreSQL connection named `pgsql_telemetry`.

Conceptually:

```dotenv
TELEMETRY_DATABASE_HOST=
TELEMETRY_DATABASE_PORT=5432
TELEMETRY_DATABASE_NAME=
TELEMETRY_DATABASE_USERNAME=
TELEMETRY_DATABASE_PASSWORD=
```

The current telemetry PostgreSQL deployment is Supabase-hosted.

Do not copy Supabase credentials into GitHub, documentation, screenshots, or Docker images.

## Redis

Redis is used for application cache/queue support.

```dotenv
REDIS_HOST=
REDIS_PORT=6379
REDIS_PASSWORD=
```

The exact queue/cache configuration is environment-specific.

## Queue configuration

The telemetry processing design currently documents:

- Maximum events per processing job: 250
- Maximum attempts: 3
- Job timeout: 60 seconds
- Queue retry-after: 90 seconds

See [Queue Worker](architecture/queue-worker.md) and [Async Processing](architecture/async-processing-final.md).

## Angular configuration

The frontend needs an API base URL appropriate to the environment.

Keep environment-specific endpoints outside hard-coded application logic where the frontend build configuration supports it.

## Render configuration

The current CI/CD workflow requires a GitHub secret:

`RENDER_API_KEY`

The workflow also targets separate Render services for:

- backend
- frontend

Render service environment variables must contain the runtime configuration required by each service. Local `.env` files are not automatically used by Render.

The production workflow is defined in:

`.github/workflows/ci.yml`

## AWS/Terraform configuration

Terraform uses variables for environment, AWS region, project name, image tags, compute sizing, database connectivity and Redis connectivity.

The repository provides an example production variables file under:

`infrastructure/terraform/production.tfvars.example`

Do not put real passwords, API keys, database credentials, or tokens into `.tfvars` files committed to Git.

## Testing configuration

CI uses:

- PHP 8.4
- SQLite for Laravel tests
- Redis service
- Node.js 22 for Angular
- Terraform 1.16.0

The CI workflow copies `backend/.env.testing` to `.env` before Laravel tests.

This means CI test configuration is intentionally different from production database configuration.

## Configuration change checklist

When changing a configuration value:

1. Update the appropriate environment/deployment secret store.
2. Update the example/template documentation if the variable itself is new.
3. Verify Laravel configuration loading.
4. Run backend/frontend tests.
5. Run Docker validation when runtime configuration changes.
6. Run Terraform validation when infrastructure variables/resources change.
7. Confirm the deployed service has the expected value without exposing the secret.
