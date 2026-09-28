# Production Release Engineering

## 61.28 Scope

This document defines the release controls for the Solar Energy Monitoring & Asset Management Platform.

The release path is:

```text
Change
  ↓
Pull Request / main
  ↓
Frontend tests + production build
  ↓
Backend tests + syntax + Pint
  ↓
Docker build + smoke tests
  ↓
Terraform format + validate
  ↓
Render deployment (main)
  ↓
Render deployment status
  ↓
Production health verification
  ↓
Post-release monitoring
```

## Release Gates

A release must not proceed when a required automated gate fails.

### Application

- Backend test suite passes.
- Frontend test suite passes.
- Frontend production build passes.
- PHP syntax validation passes.
- Laravel Pint validation passes.
- Composer validation passes.

### Containers

- Backend image builds successfully.
- Nginx image builds successfully.
- Backend container starts Laravel successfully.
- Nginx configuration validates.
- Docker Compose configuration validates.

### Infrastructure

- Terraform formatting passes.
- Terraform initializes without a backend for CI validation.
- Terraform validation passes.
- Production infrastructure changes require separate AWS access and review.

### Deployment

Render production deployment is gated behind the successful Docker, Terraform, frontend, and backend jobs.

The deployment workflow:

1. Uses the commit SHA being built.
2. Requires `RENDER_API_KEY`.
3. Deploys backend and frontend services.
4. Waits for each Render deployment to reach `live`.
5. Fails the workflow when Render reports a deployment failure or timeout.

## Frontend API Routing

The production Angular application uses:

```text
/api/v1
```

rather than a hardcoded Render hostname.

The frontend Nginx container proxies `/api/` to the Render backend using `BACKEND_BASE_URL`.

This keeps the browser-facing API path same-origin and avoids coupling the compiled frontend artifact to a particular Render service hostname.

The Render service definition remains the source of deployment-time backend routing:

```text
Browser
  ↓
Frontend /api/*
  ↓
Nginx proxy
  ↓
BACKEND_BASE_URL
  ↓
Laravel API
```

## Release Traceability

Every production release must be traceable to:

- Git commit SHA
- CI workflow run
- frontend build
- backend test result
- Docker build result
- Terraform validation result
- Render deployment result

Do not report a deployment as successful merely because the workflow started. The deployment job must report the provider's terminal deployment status.

## Rollback

For Render:

1. Identify the last known-good commit.
2. Confirm the corresponding deployment in Render.
3. Redeploy the known-good commit.
4. Wait for the provider deployment to become `live`.
5. Verify backend readiness.
6. Verify frontend health.
7. Verify application behavior.
8. Monitor logs and error rates.

For the AWS/ECS path, the existing manual deployment workflow supports rollback by Git SHA. AWS execution is not validated in this project because no AWS account is currently available.

## Configuration and Secrets

Production credentials must remain outside source control.

Required Render configuration includes:

- `APP_KEY`
- application URL configuration
- MySQL connection credentials
- telemetry PostgreSQL credentials
- Redis connection details
- Sanctum stateful-domain configuration
- frontend backend proxy URL

The repository may contain non-secret defaults and examples, but not production credentials.

## Go-Live Checklist

Before a production release:

- [ ] All CI jobs pass.
- [ ] Production frontend build passes.
- [ ] Backend regression suite passes.
- [ ] Docker images build successfully.
- [ ] Terraform validation passes.
- [ ] Production secrets are configured in Render.
- [ ] Database connectivity is available.
- [ ] Telemetry database connectivity is available.
- [ ] Redis connectivity is available.
- [ ] Backend readiness endpoint returns HTTP 200.
- [ ] Frontend health endpoint returns HTTP 200.
- [ ] Telemetry ingestion smoke test passes.
- [ ] Queue processing is healthy.
- [ ] DLQ remains observable.
- [ ] Last known-good release is identifiable.
- [ ] Rollback procedure is available.

## Current Constraint

AWS infrastructure remains a validated, version-controlled deployment path but is not considered deployed or production-validated until an AWS account and required permissions are available.

Render is the active application deployment target for the current release path.
