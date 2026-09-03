# Observability Standards

## 1. Purpose

This document defines the observability engineering standards for the Solar Energy Monitoring & Asset Management Platform.

Observability must allow engineering and operations teams to understand:

- What the system is doing
- Whether the system is healthy
- Where failures occur
- Why failures occur
- How failures affect users
- How telemetry flows through the platform
- Whether capacity is approaching operational limits

The platform uses three primary observability signals:

1. Logs
2. Metrics
3. Traces

These signals should work together rather than operate as isolated monitoring systems.

---

## 2. Observability Principles

The platform follows these principles:

1. Instrument important system boundaries.
2. Prefer structured machine-readable logs.
3. Use consistent correlation identifiers.
4. Measure both technical and business behavior.
5. Make asynchronous processing traceable.
6. Never log secrets or unnecessary sensitive data.
7. Alert on actionable conditions.
8. Prefer symptoms and service-level indicators over noisy infrastructure alerts.
9. Retain enough information for incident investigation.
10. Observability must itself be reliable and testable.

---

## 3. Observability Architecture

The primary observability flow is:

Application
→ Logs
→ CloudWatch

Application
→ Metrics
→ CloudWatch

Application / infrastructure
→ Traces and correlation identifiers
→ Operational investigation

ECS, ALB, RDS, Redis, queues, and application workloads should expose relevant operational signals.

---

## 4. Structured Logging

Application logs should use structured logging wherever practical.

Structured logs should contain predictable fields such as:

- timestamp
- level
- service
- environment
- message
- request_id
- correlation_id
- user_id where appropriate
- tenant_id where appropriate
- event_id where appropriate
- job_id where appropriate
- operation
- duration_ms
- error information

Logs should be machine searchable.

---

## 5. Log Levels

Use consistent log levels.

### DEBUG

Detailed diagnostic information intended primarily for development or temporary troubleshooting.

### INFO

Normal significant application events.

Examples:

- Request accepted
- Job processed
- Telemetry batch accepted
- Scheduled task completed

### WARNING

Unexpected behavior that does not immediately indicate service failure.

Examples:

- Retry initiated
- Slow operation
- Approaching capacity threshold
- Recoverable external-service failure

### ERROR

An operation failed and requires investigation or recovery.

Examples:

- Database operation failure
- Job processing failure
- External dependency failure

### CRITICAL

A major condition affecting the availability or integrity of the platform.

Examples:

- Application cannot initialize
- Critical dependency unavailable
- Major data-processing failure

---

## 6. Logging Rules

Logs must:

- Be meaningful.
- Be structured.
- Include useful context.
- Avoid sensitive information.
- Avoid excessive duplication.
- Avoid logging the same error repeatedly at multiple layers without additional value.

Application code should log an error where the failure can be meaningfully handled or investigated.

---

## 7. Sensitive Data in Logs

Never log:

- Passwords
- Access tokens
- API keys
- Database credentials
- Private keys
- Session secrets

Sensitive business payloads should be minimized.

Telemetry payloads should not be logged in full unless explicitly required for controlled debugging.

---

## 8. Correlation ID

Every externally initiated request should have a correlation identifier.

The identifier should allow engineers to trace related operations across system boundaries.

Example:

Request
→ Controller
→ Service
→ Queue
→ Worker
→ Database
→ DLQ

The same correlation context should be propagated across asynchronous boundaries where practical.

---

## 9. Request ID

HTTP requests should have a request identifier.

The identifier should be:

- Unique
- Safe to log
- Returned or exposed through appropriate response headers where useful
- Included in relevant application logs

Request IDs should not contain secrets or business-sensitive information.

---

## 10. Event ID

Telemetry events must retain their event ID throughout processing.

Event ID should be included in relevant logs for:

- Ingestion
- Validation
- Queue processing
- Retry
- Failure
- DLQ insertion
- Replay

This enables event-level investigation.

---

## 11. Job Observability

Background jobs should expose sufficient information to understand:

- Job type
- Job ID
- Queue
- Attempt number
- Start time
- Completion time
- Duration
- Result
- Failure reason

Queue workers should log retry and failure transitions.

---

## 12. Telemetry Observability

Telemetry processing should expose operational signals including:

- Events received
- Events accepted
- Events rejected
- Events duplicated
- Processing latency
- Processing failures
- Retry count
- DLQ count
- Replay count
- Batch size
- Processing throughput

---

## 13. Telemetry Latency

Telemetry latency should be measured across relevant stages.

Important timestamps include:

- Event timestamp
- Received timestamp
- Processing start
- Processing completion

These allow engineers to distinguish:

- Device delay
- Network delay
- Queue delay
- Processing delay
- Database delay

---

## 14. Queue Metrics

Queue-based workloads should expose:

- Queue depth
- Messages processed
- Messages failed
- Retry count
- Processing duration
- Oldest message age
- Worker utilization

Queue depth alone should not be treated as the only queue-health indicator.

---

## 15. Queue Backlog

A sustained increase in queue backlog may indicate:

- Increased ingestion rate
- Insufficient workers
- Slow database operations
- Downstream dependency problems
- Poison messages
- Worker failures

Alerts should consider backlog age and processing rate, not only queue depth.

---

## 16. Retry Observability

Retries must be observable.

Record:

- Event/job identifier
- Attempt number
- Failure reason
- Retry decision
- Retry delay where useful
- Final outcome

Repeated retries should be distinguishable from normal transient failures.

---

## 17. Dead Letter Queue Observability

DLQ monitoring should include:

- Number of pending DLQ events
- DLQ insertion rate
- Replay rate
- Replay failures
- Oldest DLQ event age
- DLQ growth over time

A sudden increase in DLQ volume should trigger investigation.

---

## 18. DLQ Replay Observability

Replay operations should record:

- Event ID
- Actor
- Replay time
- Previous status
- New status
- Replay result
- Failure reason where applicable

Replay must be traceable to an operational action.

---

## 19. API Metrics

API services should measure:

- Request count
- Success count
- Error count
- HTTP status distribution
- Request latency
- Endpoint throughput
- Rate-limit events

Important endpoints should have endpoint-level visibility.

---

## 20. API Latency

Latency should be measured using meaningful percentiles.

Where practical, monitor:

- p50
- p95
- p99

Average latency alone should not be used as the sole performance indicator.

---

## 21. Error Metrics

Track errors by meaningful dimensions such as:

- Endpoint
- Service
- Error category
- HTTP status
- Dependency
- Job type

High-cardinality dimensions should be controlled to avoid excessive metric cost.

---

## 22. Business Metrics

Observability must include business-level indicators.

Examples:

- Active solar assets
- Active devices
- Telemetry events received
- Telemetry events successfully processed
- Telemetry rejection rate
- Asset availability
- Maintenance events
- Alert volume

Business metrics help identify customer-impacting problems that infrastructure metrics may miss.

---

## 23. Infrastructure Metrics

Infrastructure monitoring should include relevant signals for:

- ECS
- ALB
- RDS MySQL
- PostgreSQL
- Redis
- Network resources
- Containers

Metrics should focus on resource saturation, errors, latency, and availability.

---

## 24. ECS Observability

ECS services should expose operational information for:

- Running tasks
- Desired tasks
- CPU utilization
- Memory utilization
- Task restarts
- Deployment state
- Container failures

Container Insights should be used where enabled by the production architecture.

---

## 25. ALB Observability

Monitor:

- Request count
- Target response time
- HTTP 4xx
- HTTP 5xx
- Healthy targets
- Unhealthy targets
- Rejected connections

ALB health signals should be correlated with application-level metrics.

---

## 26. Database Observability

Database monitoring should include:

- CPU utilization
- Memory pressure where available
- Connection count
- Connection saturation
- Query latency
- Storage utilization
- Read/write throughput
- Replication health where applicable
- Error rates

Application metrics should be used alongside database infrastructure metrics.

---

## 27. Redis Observability

Monitor relevant Redis signals such as:

- CPU
- Memory
- Connections
- Cache hit/miss behavior
- Evictions
- Network throughput
- Errors
- Availability

Redis degradation should be distinguishable from application failures.

---

## 28. Health Checks

The application must expose health endpoints appropriate to the deployment architecture.

Health checks should distinguish between:

- Process is alive
- Application is ready to serve traffic
- Critical dependencies are available

Health checks must remain lightweight.

---

## 29. Liveness

Liveness should answer:

> Is the application process functioning?

Liveness checks should avoid unnecessary dependency calls.

A temporary database outage should not automatically cause an otherwise healthy process to be considered dead.

---

## 30. Readiness

Readiness should answer:

> Should this instance receive traffic?

Readiness may validate critical dependencies where appropriate.

An instance that cannot safely serve requests should fail readiness.

---

## 31. Dependency Health

External dependencies should be monitored independently.

Examples:

- Database
- Redis
- Queue
- AWS services
- Third-party APIs

Dependency failures should be represented clearly in application and infrastructure telemetry.

---

## 32. Distributed Tracing

Distributed tracing should be introduced where it provides meaningful operational value.

Useful trace boundaries include:

- HTTP request
- Service operation
- Database query
- Queue publish
- Queue consumption
- External service call

Tracing must not become a substitute for useful logs and metrics.

---

## 33. Trace Context

Trace context should propagate across asynchronous processing where supported.

For example:

HTTP request
→ telemetry ingestion
→ queue message
→ worker
→ database

This allows a production incident to be investigated across synchronous and asynchronous boundaries.

---

## 34. Alerting Principles

Alerts must be:

- Actionable
- Specific
- Severity-aware
- Linked to operational impact
- Resistant to unnecessary noise

Not every warning should become an alert.

---

## 35. Alert Severity

A simple severity model should be used.

### Critical

Immediate customer or production impact.

### High

Significant degradation requiring prompt investigation.

### Medium

Operational issue that should be investigated during normal operations.

### Low

Informational or capacity-planning signal.

---

## 36. Availability Alerts

Alert on meaningful service availability degradation.

Examples:

- Sustained ALB 5xx errors
- No healthy ECS targets
- Service deployment failure
- Critical application health failure

---

## 37. Latency Alerts

Alert when latency exceeds defined service objectives for a sustained period.

Avoid alerting on isolated spikes unless the spike itself represents a known critical event.

---

## 38. Queue Alerts

Alert on conditions such as:

- Sustained queue backlog
- Excessive oldest-message age
- Worker processing failure
- Repeated retry exhaustion
- Rapid DLQ growth

---

## 39. Database Alerts

Alert on:

- Critical storage pressure
- Sustained high CPU
- Connection exhaustion
- Availability failures
- Significant latency degradation
- Backup failures where operationally relevant

---

## 40. Security Alerts

Security-relevant alerts may include:

- Authentication failure spikes
- Authorization failure spikes
- Suspicious administrative operations
- Unexpected deployment activity
- IAM anomalies
- Security scanning failures

Security alerts should integrate with the security incident-response process.

---

## 41. Dashboard Standards

Production dashboards should provide a layered view.

### Executive/service health

- Availability
- Error rate
- Latency
- Throughput

### Application

- API performance
- Queue processing
- Telemetry processing
- DLQ

### Infrastructure

- ECS
- ALB
- RDS
- Redis

### Business

- Assets
- Telemetry
- Operational events

---

## 42. Golden Signals

The primary service-health view should consider:

1. Latency
2. Traffic
3. Errors
4. Saturation

These signals provide a concise view of overall service health.

---

## 43. Saturation

Saturation measures how close a resource is to its operational limit.

Examples:

- CPU
- Memory
- Database connections
- Storage
- Queue capacity
- Worker capacity

Saturation metrics should support capacity planning before an outage occurs.

---

## 44. SLI

Service Level Indicators should measure actual service behavior.

Examples:

- Successful API requests
- API latency
- Telemetry processing success
- Telemetry processing latency
- Queue processing success

---

## 45. SLO

Service Level Objectives should define target service behavior.

Examples may include:

- API availability
- API latency
- Telemetry processing success
- Telemetry processing delay

SLO values should be established based on actual business requirements and production behavior rather than arbitrary targets.

---

## 46. Error Budget

Where SLOs are adopted, the corresponding error budget should guide engineering priorities.

Repeatedly consuming the error budget should trigger:

- Reliability investigation
- Capacity review
- Performance work
- Incident review
- Engineering prioritization

---

## 47. Log Retention

Log retention must balance:

- Incident investigation
- Compliance
- Storage cost
- Operational usefulness

Retention periods should be explicitly configured for production log groups.

---

## 48. Metric Cardinality

Metrics must avoid uncontrolled cardinality.

Do not create metric dimensions from unrestricted values such as:

- Full request URLs
- Arbitrary user input
- Raw event payloads
- High-volume unique IDs

Event IDs should generally remain in logs/traces rather than metric dimensions.

---

## 49. Observability Cost

Observability itself creates infrastructure cost.

Control cost through:

- Appropriate log levels
- Log retention
- Metric cardinality
- Sampling where appropriate
- Payload minimization
- Dashboard lifecycle management

Cost reduction must not remove signals required for incident investigation.

---

## 50. Observability Testing

Observability must be tested.

Tests should verify:

- Health endpoints work
- Important operations emit logs
- Correlation IDs propagate
- Queue processing is traceable
- Failures produce useful diagnostics
- Alerts trigger under expected conditions
- Sensitive data is not logged

---

## 51. Failure Investigation Flow

A standard investigation flow should be:

1. Detect alert
2. Identify affected service
3. Check golden signals
4. Check application errors
5. Trace request/correlation ID
6. Inspect queue state
7. Inspect database/Redis health
8. Inspect recent deployments
9. Identify root cause
10. Apply mitigation
11. Validate recovery
12. Document findings

---

## 52. Deployment Observability

Every deployment should be observable.

Monitor:

- Deployment start
- Task replacement
- Health-check status
- Error rate
- Latency
- Queue behavior
- Database behavior
- Deployment completion

A deployment should be considered healthy only after application-level behavior is verified.

---

## 53. Observability During Rollback

During rollback, monitor:

- Deployment state
- Error rate
- Latency
- Health checks
- Queue backlog
- Database behavior
- Application logs

Rollback success must be validated through service behavior rather than deployment status alone.

---

## 54. Operational Runbooks

Important alerts should have corresponding operational runbooks.

A runbook should explain:

- What the alert means
- Likely causes
- What to inspect
- Immediate mitigation
- Escalation path
- Recovery validation
- Follow-up actions

---

## 55. Observability Governance Checklist

Before production release, verify:

- [ ] Structured application logging configured
- [ ] Log levels defined
- [ ] Sensitive data excluded from logs
- [ ] Request IDs implemented
- [ ] Correlation IDs implemented
- [ ] Event IDs traceable
- [ ] Queue processing observable
- [ ] Retry behavior observable
- [ ] DLQ observable
- [ ] API metrics available
- [ ] Infrastructure metrics available
- [ ] Database metrics available
- [ ] Redis metrics available
- [ ] Health checks configured
- [ ] Readiness behavior validated
- [ ] Critical alerts configured
- [ ] Dashboards available
- [ ] Log retention configured
- [ ] Metric cardinality reviewed
- [ ] Observability cost reviewed
- [ ] Failure investigation runbooks available

---

## 56. Engineering Principle

Observability is successful when an engineer can answer:

> What happened?

> Where did it happen?

> Why did it happen?

> Who or what was affected?

> Has the system recovered?

The platform should make these answers available through correlated logs, metrics, traces, and operational dashboards.
