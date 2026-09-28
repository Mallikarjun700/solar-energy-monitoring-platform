# Monitoring and Observability

## Monitoring goals

Monitor the platform from four perspectives:

1. Application health
2. Queue/worker health
3. Telemetry pipeline health
4. Deployment/infrastructure health

## Application health

Use:

`GET /api/v1/ready`

A healthy response reports:

- database: `ok`
- queue: `ok`

HTTP 503 indicates the application is not ready.

## Telemetry health

Use:

`GET /api/v1/telemetry/health`

with an authorized telemetry-read token.

Monitor:

- ingestion availability
- processing failures
- queue depth
- oldest queued job
- telemetry processing latency
- telemetry database errors

## Queue monitoring

Important signals:

### Queue depth

Number of pending jobs.

A continuously increasing depth can indicate that ingestion is faster than worker processing.

### Queue age

Age of the oldest pending job.

Queue age is often more useful than depth alone because a small queue can still contain a very old job.

### Failed jobs

Track repeated job failures and correlate them with deployment or database changes.

### Worker availability

A queue with no active workers can accumulate indefinitely.

## DLQ monitoring

Track:

- DLQ record count
- oldest DLQ record age
- failure reason
- affected device
- replay attempts
- unresolved records

A growing DLQ indicates processing failures that require investigation.

## Application logs

Use structured logs for:

- authentication failures
- database errors
- queue failures
- telemetry processing errors
- DLQ transitions
- deployment/readiness failures

Do not log passwords, access tokens, database credentials, or other secrets.

## Deployment monitoring

For Render, verify deployment status reaches `live`.

For AWS, verify:

- ECS service rollout
- ALB target health
- application readiness
- expected container image tag

## Recommended operational dashboard

At minimum expose:

| Signal | Purpose |
|---|---|
| API readiness | Application availability |
| API error rate | Request health |
| Queue depth | Processing pressure |
| Queue age | Processing delay |
| Failed jobs | Worker failures |
| DLQ count | Unresolved processing failures |
| Telemetry ingestion rate | Input load |
| Telemetry processing latency | Pipeline performance |
| DB connectivity | Persistence health |
| Deployment status | Release health |

## Alerting principle

Alerts should be actionable. Prefer thresholds that identify sustained degradation rather than transient noise.
