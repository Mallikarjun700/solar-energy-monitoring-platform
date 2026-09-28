# Incident Response

## Objective

Restore service safely while preserving enough evidence to understand the failure.

## Severity model

### Critical

Examples:

- application unavailable
- telemetry ingestion unavailable
- widespread data persistence failure
- production deployment causing sustained outage

### High

Examples:

- queue continuously growing
- significant DLQ growth
- one major subsystem unavailable

### Medium

Examples:

- isolated device/asset failures
- elevated but recoverable processing errors

## Response procedure

### 1. Detect

Identify the failing signal:

- readiness
- API errors
- telemetry health
- queue depth/age
- failed jobs
- DLQ growth
- deployment status

### 2. Stabilize

Stop additional damage where necessary.

Examples:

- pause a failing deployment
- stop repeated replay of a known-bad payload
- reduce ingestion pressure when operationally possible

### 3. Diagnose

Collect:

- deployment commit
- application logs
- queue/worker logs
- failed job details
- DLQ records
- database errors
- infrastructure health

Do not expose secrets while collecting evidence.

### 4. Recover

Choose the smallest safe recovery action:

- correct configuration
- restart worker
- roll back deployment
- restore service dependency
- replay corrected DLQ events

### 5. Verify

Confirm:

- readiness is healthy
- APIs respond
- queue age is recovering
- DLQ is not continuing to grow
- telemetry is being persisted
- frontend can authenticate

### 6. Close

Document:

- incident timeline
- root cause
- customer/operational impact
- recovery action
- follow-up prevention work

## Deployment incident

A deployment is not successful merely because the workflow reached the deployment stage.

For Render, success requires the service deployment to reach `live`.

For AWS, verify ECS rollout, ALB health, readiness, and deployed image version.

## Telemetry incident

For telemetry failures, preserve failed events and their error reasons before attempting bulk replay.

Use the DLQ as the controlled recovery boundary.

## Recovery principle

Prefer reversible, observable changes over multiple simultaneous changes during an incident.
