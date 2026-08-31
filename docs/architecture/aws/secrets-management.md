# AWS Secrets Management

## Purpose

Application credentials must not be hard-coded into Terraform,
Docker images, ECS task definitions, or Git.

## Local development

Local Docker Compose may use development-only credentials supplied
through environment variables.

Example:

- DB_USERNAME
- DB_PASSWORD
- TELEMETRY_DB_USERNAME
- TELEMETRY_DB_PASSWORD

These values must never be committed to Git.

## AWS deployment

Production credentials will be stored in AWS Secrets Manager.

ECS will receive secret values through Secrets Manager references.

Planned secret categories:

### Application database

- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD

### Telemetry database

- TELEMETRY_DB_HOST
- TELEMETRY_DB_PORT
- TELEMETRY_DB_DATABASE
- TELEMETRY_DB_USERNAME
- TELEMETRY_DB_PASSWORD

### Redis

Redis credentials will be introduced if authentication is enabled
for the production Redis implementation.

## Security requirements

- Never commit production credentials.
- Never place production passwords directly in Terraform variables.
- Never place production credentials in Dockerfiles.
- Never place production credentials in Docker images.
- ECS task definitions should reference Secrets Manager.
- Secret ARNs may be supplied through deployment variables.
