# Release Process

## Release lifecycle

```text
Change
  |
  v
Pull Request
  |
  v
CI validation
  |
  v
Merge to main
  |
  v
Production deployment
  |
  v
Health verification
  |
  v
Release monitoring
```

## 1. Prepare the change

Keep changes focused and update documentation when behavior or architecture changes.

For architecture-sensitive changes, update the relevant flow/runbook at the same time.

## 2. Open a pull request

The CI workflow runs for pull requests targeting `main`.

Expected validation includes:

- Angular tests/build
- Laravel tests
- PHP syntax
- code style
- Docker build/smoke checks
- Docker Compose validation
- Terraform validation

## 3. Merge to main

The automated Render deployment is triggered by a push to `main`.

The production deployment uses the commit SHA that triggered the workflow.

## 4. Render verification

The workflow starts backend and frontend deployments and waits for each deployment status.

A deployment is considered successful only when the service reaches:

`live`

Failure states cause the workflow to fail.

## 5. AWS release path

For AWS deployments, the release process includes:

1. Authenticate through GitHub OIDC.
2. Build and push images to ECR.
3. Run the migration task.
4. Deploy ECS services.
5. Verify ALB target health.
6. Verify application readiness.
7. Verify deployed image tags.

## 6. Database changes

Database migrations must be reviewed with deployment ordering in mind.

Prefer backward-compatible changes when old and new application versions may overlap.

For destructive migrations:

1. back up affected data where appropriate
2. deploy compatibility changes first
3. migrate data
4. remove obsolete paths only after consumers are migrated

## 7. Rollback

If a release is unhealthy:

1. Stop further rollout.
2. Identify the last known-good commit/image.
3. Roll back the application using the deployment platform.
4. Verify readiness and critical application flows.
5. Investigate the failed release before retrying.

Do not use a database rollback as a default application rollback mechanism; database changes may not be safely reversible.

## 8. Post-release checks

Verify:

- frontend loads
- login works
- readiness is healthy
- telemetry ingestion accepts events
- queue workers process jobs
- telemetry appears after asynchronous processing
- alerts are accessible
- no unexpected DLQ growth occurs

## Documentation requirement

A release that changes a public API, environment variable, deployment process, data flow, or operational behavior should update the corresponding documentation.
