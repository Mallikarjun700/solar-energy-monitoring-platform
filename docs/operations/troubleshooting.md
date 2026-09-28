# Troubleshooting Guide

## API returns 503 from readiness

### Symptoms

`GET /api/v1/ready` returns HTTP 503.

### Check

1. Verify MySQL connectivity.
2. Verify the queue database/table is available.
3. Inspect Laravel logs.
4. Confirm the deployed environment variables are present.

## Telemetry accepted but not appearing

### Symptoms

The telemetry endpoint returns HTTP 202 but the event is not immediately visible.

### Explanation

Telemetry processing is asynchronous.

Check:

1. Queue depth.
2. Running queue workers.
3. Failed jobs.
4. Telemetry worker logs.
5. Telemetry PostgreSQL connectivity.
6. DLQ records.

Do not interpret HTTP 202 as proof that final persistence has already completed.

## Queue is growing

### Possible causes

- worker is stopped
- worker throughput is too low
- database latency
- repeated job failures
- sudden ingestion spike

### Actions

1. Inspect worker logs.
2. Check failed jobs.
3. Check queue age.
4. Check database health.
5. Scale worker capacity where the deployment target supports it.
6. Restart workers gracefully after correcting configuration.

Use:

```bash
php artisan queue:restart
```

Then verify new workers consume the queue.

## Events are entering the DLQ

Check:

- failure reason
- payload validity
- device relationship
- telemetry database availability
- recent deployments
- retry count

If the underlying issue is corrected, replay through the DLQ API rather than manually inserting telemetry.

## Duplicate telemetry appears

Check the event identity/idempotency implementation and database uniqueness constraints.

The expected design is that duplicate delivery of the same logical event does not create duplicate stored telemetry.

## Frontend cannot authenticate

Check:

1. Login endpoint URL.
2. Browser network response.
3. Sanctum token handling.
4. Token storage.
5. Backend authentication logs.
6. CORS/deployment configuration if applicable.

## Frontend receives 403

The user may be authenticated but lack the required ability or role.

Check the endpoint ability and the user's assigned authorization.

## Render deployment fails

Check the GitHub Actions job first.

The deployment workflow explicitly treats these Render states as failures:

- build_failed
- update_failed
- pre_deploy_failed
- canceled

Then inspect the Render service deployment logs and environment variables.

## AWS deployment fails

Check:

1. GitHub OIDC authentication.
2. ECR image push.
3. migration task.
4. ECS service rollout.
5. ALB target health.
6. application readiness.
7. deployed image tag.

Use the AWS deployment runbook for the detailed procedure.

## Terraform validation fails

Run:

```bash
terraform fmt -check -recursive
terraform init -backend=false
terraform validate
```

Fix formatting or configuration errors before applying infrastructure changes.

## Docker build fails

Build each image independently:

```bash
docker build -f docker/php/Dockerfile .
docker build -f docker/nginx/Dockerfile .
```

Then validate Compose:

```bash
docker compose -f docker-compose.yml config
```
