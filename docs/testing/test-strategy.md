# Test Strategy

## Test layers

The project validates multiple layers before deployment.

```text
Code
 |
 +--> PHP syntax
 +--> Laravel/PHPUnit tests
 +--> Angular tests
 +--> Angular production build
 +--> Docker build + smoke tests
 +--> Docker Compose validation
 +--> Terraform validation
 |
 v
Deployment
```

## Backend

CI runs:

- PHP syntax checks
- Composer validation
- Laravel Pint
- PHPUnit/Laravel tests

CI uses PHP 8.4 for the test environment.

The test environment uses SQLite for Laravel tests and a Redis service.

This is intentional: CI tests should be deterministic and should not require production MySQL or Supabase credentials.

## Frontend

CI runs:

```bash
npm ci
npm test -- --watch=false
npm run build -- --configuration production
```

Node.js 22 is used in CI.

## Docker

CI builds:

- PHP application image
- Nginx image

It also performs smoke checks such as:

- PHP version
- Laravel artisan availability
- Nginx configuration validation
- expected application directories
- Docker Compose configuration

## Terraform

CI validates:

```bash
terraform fmt -check -recursive
terraform init -backend=false
terraform validate
```

This verifies syntax and configuration consistency without applying infrastructure.

## Flow-level test priorities

High-value application flows should be tested around:

- authentication
- authorization
- plant/asset/device relationships
- telemetry validation
- telemetry idempotency
- queue processing
- retry behavior
- DLQ transition
- DLQ replay
- alert acknowledgement
- alert resolution
- readiness

## Deployment gate

The Render deployment job requires successful completion of the required test/build/validation jobs.

Deployment is only triggered automatically for pushes to `main`.

## Test data principle

Do not use production credentials or production telemetry databases for automated tests.

Test fixtures should be deterministic and safe to rerun.
