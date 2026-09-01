# AWS Secrets Management

## Purpose

Production database credentials must not be stored in Git, Docker images,
Terraform source files, or GitHub Actions workflow files.

The ECS workloads use AWS Secrets Manager for runtime secret injection.

## Secrets

The application uses two secret references:

```text
database_secret_arn
telemetry_database_secret_arn
