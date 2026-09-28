# Security

## Security boundaries

The platform has several security layers:

```text
Browser
  |
  v
Authentication
  |
  v
Sanctum token
  |
  v
Ability / role authorization
  |
  v
Application validation
  |
  v
Database
```

## Authentication

Laravel Sanctum protects authenticated API routes.

The frontend uses an authentication guard for protected application routes.

## Authorization

Use fine-grained abilities such as:

- `plants:read`
- `plants:write`
- `assets:read`
- `assets:write`
- `devices:read`
- `devices:write`
- `telemetry:read`
- `telemetry:write`
- `dlq:read`
- `dlq:replay`
- `alerts:read`
- `alerts:acknowledge`
- `alerts:resolve`

Administration is separately protected by the admin role.

## Telemetry protection

Telemetry ingestion is protected by:

- authentication
- ability authorization
- throttling
- request-size validation
- idempotency

These controls reduce abuse and protect the asynchronous processing pipeline.

## Secrets

Never commit:

- API keys
- passwords
- database credentials
- Sanctum secrets/tokens
- cloud credentials
- Render API keys

Use GitHub Secrets, Render environment variables, AWS secret-management mechanisms, or the appropriate deployment secret store.

## GitHub Actions

The Render deployment requires `RENDER_API_KEY` from GitHub Secrets.

The AWS deployment path uses GitHub/AWS OIDC rather than storing long-lived AWS credentials in the repository workflow.

## Database security

Use separate credentials and least-privilege access for:

- primary application database
- telemetry database
- infrastructure services

Do not expose database ports publicly unless required and properly secured.

## Logging

Never log:

- passwords
- bearer/access tokens
- secret environment variables
- database passwords

Logs should contain enough context to diagnose failures without exposing credentials.

## Container security

Use the repository's Dockerfiles as the source of truth for runtime images.

Keep base images and package dependencies maintained and rebuild images regularly.

## Operational security

DLQ replay should remain permission-controlled because replay can reprocess production data.

Administrative operations should be auditable where the application implementation supports audit logging.
