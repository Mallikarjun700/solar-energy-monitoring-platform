# Getting Started

This guide sets up the Solar Energy Monitoring & Asset Management Platform locally.

## 1. Prerequisites

Install:

- Git
- PHP 8.5+
- Composer 2.x
- Node.js 22+
- npm
- MySQL
- PostgreSQL client/tools
- Redis
- Docker and Docker Compose
- Terraform 1.16.0 when working with AWS infrastructure

The application consists of:

- `frontend/` — Angular application
- `backend/` — Laravel API
- MySQL — primary application database
- PostgreSQL/Supabase — telemetry database
- Redis — cache/queue support
- Docker — reproducible runtime images
- `infrastructure/terraform/` — AWS infrastructure definition

## 2. Clone the repository

```bash
git clone https://github.com/Mallikarjun700/solar-energy-monitoring-platform.git
cd solar-energy-monitoring-platform
```

## 3. Backend setup

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Configure the application environment before running migrations.

For a local MySQL database, configure the normal Laravel `DB_*` variables.

For telemetry, configure the `pgsql_telemetry` connection with the PostgreSQL/Supabase connection values used by the environment.

Then run:

```bash
php artisan migrate
php artisan storage:link
```

Start the API:

```bash
php artisan serve
```

## 4. Frontend setup

In another terminal:

```bash
cd frontend
npm ci
npm start
```

The Angular application uses the configured backend API URL for API calls.

## 5. Redis

Make sure Redis is running and reachable using the host/port configured in Laravel.

A local Docker Redis instance can be used when preferred:

```bash
docker run --name solar-redis -p 6379:6379 -d redis:7-alpine
```

## 6. Queue worker

Telemetry ingestion is asynchronous. Run a queue worker in development:

```bash
cd backend
php artisan queue:work
```

The worker processes queued telemetry batches and invokes the telemetry processing service.

For a graceful worker restart after deployment/configuration changes:

```bash
php artisan queue:restart
```

## 7. Run tests

Backend:

```bash
cd backend
cp .env.testing .env
php artisan test
```

Frontend:

```bash
cd frontend
npm ci
npm test -- --watch=false
npm run build -- --configuration production
```

Terraform validation:

```bash
cd infrastructure/terraform
terraform init -backend=false
terraform fmt -check -recursive
terraform validate
```

## 8. Docker validation

From the repository root:

```bash
docker build -f docker/php/Dockerfile .
docker build -f docker/nginx/Dockerfile .
docker compose -f docker-compose.yml config
```

The CI workflow performs equivalent Docker smoke checks.

## 9. Local request flow

The normal development flow is:

`Angular → Laravel API → MySQL/Redis`

Telemetry uses:

`Angular/device client → Laravel telemetry endpoint → DB queue → queue worker → Supabase PostgreSQL`

## 10. Readiness check

The API exposes:

`GET /api/v1/ready`

A ready response verifies application database connectivity and the queue table check. A failure returns HTTP 503.

## 11. Common setup problems

### Composer dependencies fail

Verify the PHP version and required PHP extensions, then run:

```bash
composer diagnose
```

### Database connection fails

Verify:

- host
- port
- database name
- username
- password
- database driver

Then test Laravel's actual connection:

```bash
php artisan tinker
```

### Queue jobs do not process

Verify Redis/database queue connectivity and ensure a worker is running:

```bash
php artisan queue:work
```

### Frontend cannot reach the API

Verify the Angular environment/API URL and confirm the Laravel service is reachable from the browser.

## Related documentation

- [Configuration](configuration.md)
- [API Reference](api/api-reference.md)
- [Telemetry Flow](flows/telemetry-flow.md)
- [Deployment Flow](flows/deployment-flow.md)
