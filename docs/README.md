# Project Documentation

Welcome to the documentation for the **Solar Energy Monitoring & Asset Management Platform**.

This project is a portfolio-grade solar operations platform covering asset management, telemetry ingestion, asynchronous processing, reliability, alert handling, and automated deployment.

## Documentation map

### Architecture

- [High-Level Design](architecture/high-level-design.md)
- [Async Processing](architecture/async-processing-final.md)
- [Queue Worker](architecture/queue-worker.md)
- [Telemetry DLQ](architecture/telemetry-dlq.md)
- [Telemetry DLQ Flow](architecture/telemetry-dlq-flow.md)
- [AWS Deployment Runbook](architecture/aws/deployment-runbook.md)
- [AWS Network & Security](architecture/aws/network-security.md)
- [AWS Secrets Management](architecture/aws/secrets-management.md)
- [AWS GitHub Actions OIDC](architecture/aws/github-actions-oidc.md)
- [AWS ALB Health Checks](architecture/aws/alb-health-checks.md)

### Developer documentation

- [Getting Started](getting-started.md)
- [Configuration](configuration.md)
- [API Reference](api/api-reference.md)

### Application flows

- [Authentication Flow](flows/authentication-flow.md)
- [Plant → Asset → Device Flow](flows/plant-asset-device-flow.md)
- [Telemetry Flow](flows/telemetry-flow.md)
- [Alert Flow](flows/alert-flow.md)
- [Deployment Flow](flows/deployment-flow.md)
- [End-to-End Data Flow](architecture/data-flow.md)

### Operations

- [Monitoring](operations/monitoring.md)
- [Troubleshooting](operations/troubleshooting.md)
- [Incident Response](operations/incident-response.md)
- [Backup & Restore](architecture/operations/backup-restore.md)

### Engineering standards

- [Functional Requirements](requirements/functional-requirements.md)
- [Non-Functional Requirements](requirements/non-functional-requirements.md)
- [Database Requirements](database/db-requirements.md)
- [Test Strategy](testing/test-strategy.md)
- [Security](security/security.md)
- [Release Process](release/release-process.md)

## Deployment model

The repository currently supports two deployment paths:

1. **Render** — the current automated CI/CD deployment path for the frontend and backend.
2. **AWS ECS/Fargate** — the infrastructure-backed deployment path using Docker, ECR, ECS, ALB, Terraform, workers, and a scheduler.

These paths should not be described as the same runtime environment. Render is the current application deployment workflow; AWS is the documented infrastructure deployment path.

## Core reliability flows

The telemetry path is intentionally asynchronous:

`Client → Laravel API → Queue → Telemetry Worker → Supabase PostgreSQL`

Failures follow:

`Processing failure → Retry/backoff → Retry exhausted → DLQ → Inspect/Replay → Normal processing`

Idempotency prevents duplicate delivery from creating duplicate telemetry records.

## Source of truth

When documentation and implementation differ, verify against:

- `backend/routes/api.php`
- `frontend/src/app/app.routes.ts`
- `.github/workflows/ci.yml`
- `.github/workflows/`
- `infrastructure/terraform/`
- Dockerfiles and `docker-compose.yml`

Documentation should describe implemented behavior rather than hypothetical architecture.
