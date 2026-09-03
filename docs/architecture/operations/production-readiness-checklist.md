# Production Readiness Checklist

## 1. Purpose

This checklist defines the minimum requirements for moving the Solar Energy Monitoring Platform toward production.

The checklist separates:

- Required — must be completed before production
- Recommended — should be completed before or shortly after production
- Future Enhancement — valuable improvement that does not block the initial release

The checklist prevents production readiness from being based only on application code.

---

# 2. Production Readiness Decision

A production deployment should only proceed when all critical Required items are complete.

The final decision should consider:

    Security
      +
    Reliability
      +
    Availability
      +
    Performance
      +
    Observability
      +
    Recovery
      +
    Operational readiness

A technically working application is not automatically production-ready.

---

# 3. Infrastructure Readiness

| Check | Priority | Status |
|---|---|---|
| Terraform configuration validated | Required | Pending deployment validation |
| Terraform formatting validated | Required | Complete |
| Terraform plan reviewed | Required | Pending AWS access |
| VPC configured | Required | Complete |
| Public subnets configured | Required | Complete |
| Private application subnets configured | Required | Complete |
| Private data subnets configured | Required | Complete |
| Internet Gateway configured | Required | Complete |
| NAT configuration reviewed | Required | Complete |
| Route tables reviewed | Required | Complete |
| Security groups reviewed | Required | Complete |
| Production Terraform variables reviewed | Required | Complete |
| Terraform state management configured | Required | Pending final verification |
| Resource tagging configured | Recommended | Complete |

---

# 4. Networking Readiness

The network architecture should verify:

- public traffic enters through the ALB
- application tasks run in private subnets
- databases run in private data subnets
- Redis runs in private data subnets
- databases are not publicly accessible
- security groups enforce least-privilege connectivity
- private workloads have required outbound connectivity
- availability zones are correctly configured

Required validation:

    Internet
       ↓
      ALB
       ↓
    Private ECS
       ↓
    Private Databases / Redis

Direct internet access to databases must not be permitted.

---

# 5. IAM and Security Readiness

| Check | Priority | Status |
|---|---|---|
| ECS execution role configured | Required | Complete |
| ECS task role separated from execution role | Required | Complete |
| ECS task permissions minimized | Required | Complete |
| Secrets Manager access restricted | Required | Complete |
| GitHub OIDC configured | Required | Complete |
| GitHub deployment role configured | Required | Complete |
| iam:PassRole restricted | Required | Complete |
| Long-lived AWS credentials avoided in GitHub | Required | Complete |
| Production GitHub Environment protection configured | Required | Complete |
| Production branch restriction configured | Required | Complete |
| Security incident response documented | Required | Complete |

Before production:

- review IAM policies
- remove unnecessary permissions
- verify trust policies
- verify production environment protection
- verify no AWS access keys are stored in GitHub Actions

---

# 6. Secrets Readiness

Production secrets must be managed through a secure secret-management mechanism.

Verify:

- database passwords are not committed to Git
- application secrets are not committed to Git
- GitHub Actions does not contain long-lived AWS credentials
- ECS receives required secrets securely
- secret access is limited to required roles
- secrets can be rotated
- production and staging secrets are separated

Never place production credentials in:

- source code
- Terraform variables committed to Git
- Dockerfiles
- container images
- application logs
- public repositories

---

# 7. ECR Readiness

Verify:

- backend repository exists
- Nginx repository exists
- image scanning is enabled
- image tags are immutable
- lifecycle policies are configured
- production images use Git commit SHA identifiers
- deployment references the intended image version

Example:

    <repository>:<git-sha>

The Git SHA provides an immutable reference to the deployed application version.

---

# 8. ECS Readiness

Verify:

- ECS cluster exists
- backend task definition exists
- queue worker task definition exists
- scheduler task definition exists
- required environment variables exist
- required secrets exist
- CPU and memory are appropriate
- health checks work
- log configuration works
- desired task counts are appropriate
- deployment configuration is reviewed

Each service should have a clearly defined responsibility.

---

# 9. ALB Readiness

Verify:

- ALB is internet-facing
- ALB security group is configured
- target group is configured
- ECS tasks are registered as targets
- health check path is valid
- healthy targets receive traffic
- unhealthy tasks are removed from traffic
- HTTP/HTTPS configuration matches production requirements

Production HTTPS should be enabled when the application is exposed to real users or clients.

---

# 10. Database Readiness

## MySQL

Verify:

- RDS instance is available
- private connectivity works
- security group allows only required access
- database credentials work
- migrations are available
- backup configuration is reviewed
- storage capacity is sufficient
- connection limits are understood

## PostgreSQL

Verify:

- telemetry database is available
- private connectivity works
- security group allows only required access
- credentials work
- telemetry schema/migrations are available
- storage capacity is sufficient
- backup strategy is reviewed

---

# 11. Database Migration Readiness

Database migrations must be treated as production deployment changes.

Before production:

1. review every migration
2. test migrations against production-like data
3. verify migration order
4. verify backward compatibility
5. estimate migration duration
6. identify locking risks
7. define recovery procedure

Avoid destructive schema changes in the same deployment as application changes unless compatibility has been verified.

Prefer:

    Expand
       ↓
    Deploy application
       ↓
    Validate
       ↓
    Contract later

---

# 12. Redis Readiness

Verify:

- Redis is reachable from ECS
- encryption configuration is appropriate
- security group is restrictive
- memory capacity is sufficient
- TTL policies are defined where required
- application behavior during Redis failure is understood

Redis should not be treated as the authoritative store for persistent business data.

---

# 13. Application Readiness

Verify:

- production configuration is loaded correctly
- debug mode is disabled
- health endpoint works
- API validation works
- authentication and authorization work
- error handling is implemented
- database connections work
- Redis connections work
- queue dispatch works
- application logs are available

Production must not expose debug information through API responses or logs.

---

# 14. Telemetry Pipeline Readiness

The telemetry pipeline must verify:

    Client
      ↓
    API
      ↓
    Validation
      ↓
    Idempotency
      ↓
    Queue
      ↓
    Worker
      ↓
    Telemetry Database

Verify:

- event validation
- batch limits
- duplicate handling
- event ID uniqueness
- asynchronous dispatch
- retry behavior
- retry limits
- failure handling
- DLQ behavior
- DLQ inspection
- replay mechanism
- safe replay
- telemetry database persistence

---

# 15. Idempotency Readiness

Verify that duplicate telemetry events cannot create unintended duplicate records.

Validate:

- event_id uniqueness
- database uniqueness constraint
- duplicate request behavior
- duplicate queue delivery behavior
- retry behavior
- DLQ replay behavior

The idempotency mechanism must remain effective during retries and replays.

---

# 16. Queue Worker Readiness

Verify:

- queue connection configuration
- jobs table/schema
- worker task definition
- worker service
- retry configuration
- timeout configuration
- failure handling
- logging
- queue monitoring
- worker scaling strategy

The worker should be independently scalable from the API layer.

---

# 17. Scheduler Readiness

Verify:

- scheduler task is running
- scheduled jobs execute correctly
- duplicate execution is prevented where required
- scheduler failures are observable
- scheduler logs are available

Scheduler failures must not silently prevent critical background operations.

---

# 18. DLQ Readiness

Verify:

- failed events reach the DLQ
- retry exhaustion is handled correctly
- non-retryable failures reach the DLQ
- failure reason is preserved
- attempt count is recorded
- event identity is preserved
- DLQ status can be inspected
- replay mechanism is available
- replay is idempotent

Never blindly replay an entire DLQ without understanding the failure cause.

---

# 19. Observability Readiness

Verify:

### Logs

- API logs
- Nginx logs
- queue worker logs
- scheduler logs
- application exceptions
- deployment logs

### Metrics

- ALB
- ECS
- queue
- DLQ
- MySQL
- PostgreSQL
- Redis

### Correlation

Logs should support tracing through identifiers such as:

- Request ID
- Event ID
- Tenant ID
- Source ID
- Job ID

Secrets and sensitive credentials must never appear in logs.

---

# 20. Alerting Readiness

Critical alerts should cover:

- ALB 5xx increase
- unhealthy ECS targets
- ECS tasks below desired count
- queue backlog growth
- excessive queue age
- DLQ growth
- database storage pressure
- database connectivity failures
- Redis failures
- application health failures

Alerts should have:

- severity
- owner
- response procedure
- escalation path

---

# 21. Backup and Restore Readiness

Verify:

- MySQL backup configuration
- PostgreSQL backup configuration
- retention configuration
- restore procedure documented
- restore procedure tested
- recovery objectives documented
- backup monitoring enabled

A backup strategy is incomplete until restoration has been validated.

---

# 22. Disaster Recovery Readiness

Verify:

- RTO documented
- RPO documented
- recovery dependencies identified
- infrastructure recreation process documented
- database recovery process documented
- application deployment process documented
- DNS/network recovery considered
- secrets recovery considered

The team should understand:

> What must be rebuilt first when the primary environment is unavailable?

---

# 23. High Availability Readiness

Verify:

- multiple availability zones where required
- ALB spans appropriate subnets
- ECS can run multiple tasks
- private application networking is resilient
- critical dependencies have appropriate availability
- unhealthy ECS tasks are replaced
- deployment does not require complete service downtime

Availability decisions should match the production SLA.

---

# 24. Auto Scaling Readiness

Verify:

- API scaling policy
- queue worker scaling policy
- minimum task count
- maximum task count
- scaling signals
- cooldown/stabilization
- scale-in behavior
- scale-out behavior

Scaling policies should be load-tested before relying on them for production traffic.

---

# 25. Performance Readiness

Before production, validate:

- API latency
- telemetry ingestion throughput
- maximum supported batch size
- queue processing throughput
- database write throughput
- database query latency
- Redis latency
- concurrent users
- peak workload

Performance tests should include both normal and burst traffic.

---

# 26. Load Testing

At minimum, test:

### Normal Load

Expected production traffic.

### Peak Load

Expected maximum traffic.

### Burst Load

Sudden telemetry increase.

### Sustained Load

High traffic maintained for an extended period.

### Failure Load

Dependency failure during traffic.

### Recovery Load

System recovery while processing accumulated backlog.

Record:

- throughput
- latency
- error rate
- CPU
- memory
- queue depth
- database utilization

---

# 27. CI/CD Readiness

Verify:

- CI workflow passes
- PHP tests pass
- static checks pass
- Docker builds pass
- Terraform validation passes
- deployment workflow is valid
- OIDC authentication is configured
- ECR push works
- ECS deployment works
- service stability is verified
- production requires appropriate approval

Production deployment should use immutable image versions.

---

# 28. Deployment Readiness

A production deployment should follow:

    Build
      ↓
    Test
      ↓
    Scan
      ↓
    Push immutable images
      ↓
    Register ECS task revision
      ↓
    Deploy
      ↓
    Health checks
      ↓
    Wait for stability
      ↓
    Smoke test
      ↓
    Monitor

If deployment health checks fail, stop and investigate or roll back.

---

# 29. Rollback Readiness

Verify that the team can identify:

- current Git SHA
- previous healthy Git SHA
- current ECS task revision
- previous healthy ECS task revision

Rollback should be tested before a production incident.

Application rollback must not be confused with database rollback.

Database changes require compatibility-aware recovery procedures.

---

# 30. Security Validation

Before production:

- review authentication
- review authorization
- review API validation
- review CORS configuration
- review security headers
- review rate limiting where required
- review IAM policies
- review security groups
- review secrets
- review container images
- review dependency vulnerabilities
- review audit logging

Security should be validated independently from functional testing.

---

# 31. Cost Readiness

Verify:

- ECS task sizes reviewed
- RDS sizes reviewed
- Redis size reviewed
- ECR lifecycle policies enabled
- CloudWatch retention reviewed
- staging resources right-sized
- production resources justified
- budgets configured where applicable
- cost monitoring available

Cost optimization must not compromise production reliability.

---

# 32. Documentation Readiness

Production documentation should include:

- architecture overview
- deployment runbook
- incident response
- rollback procedure
- disaster recovery
- backup and restore
- observability
- capacity planning
- cost strategy
- security response
- operational contacts
- known limitations

Documentation should be stored with the project and version controlled.

---

# 33. Go-Live Smoke Test

Immediately after deployment verify:

1. ALB responds successfully.
2. Health endpoint returns successfully.
3. API authentication works.
4. Database connectivity works.
5. Redis connectivity works.
6. Telemetry ingestion works.
7. Queue job is created.
8. Queue worker processes the job.
9. Telemetry record is persisted.
10. Duplicate event is handled correctly.
11. Failed processing reaches the expected failure path.
12. Logs appear in CloudWatch.
13. Metrics are visible.
14. Critical alerts are operational.

---

# 34. Production Go / No-Go Decision

## GO

Production can proceed when:

- all Required security controls are complete
- infrastructure is validated
- application tests pass
- deployment succeeds
- health checks pass
- database connectivity works
- telemetry pipeline works
- observability is operational
- backup/restore strategy is validated
- rollback procedure is available
- critical operational risks are accepted

## NO-GO

Production should be blocked when:

- production credentials are exposed
- databases are unintentionally public
- deployment cannot be rolled back safely
- critical health checks fail
- telemetry data integrity is uncertain
- backup/recovery is unavailable
- critical security controls are missing
- required observability is unavailable
- infrastructure capacity is insufficient

---

# 35. Required vs Recommended vs Future

Not every architectural improvement must block the initial release.

### Required

Anything necessary for:

- security
- data integrity
- basic availability
- deployment
- rollback
- monitoring
- recovery

### Recommended

Important improvements that can follow shortly after launch.

Examples:

- advanced dashboards
- additional automation
- expanded load testing
- additional cost optimization
- deeper performance tuning

### Future Enhancement

Long-term improvements such as:

- advanced telemetry analytics
- multi-region active-active architecture
- advanced data lake integration
- sophisticated predictive scaling
- additional disaster recovery automation

This classification prevents the platform from claiming that future architecture exists before it is implemented.

---

# 36. Final Production Readiness Principle

Production readiness is not defined by whether the application starts successfully.

A production-ready system must answer:

> Can we deploy it safely?

> Can we detect failures?

> Can we recover from failures?

> Can we protect customer and system data?

> Can we scale it?

> Can we restore it?

> Can we roll it back?

> Can we operate it during an incident?

If the answer to these questions is yes, the platform has moved from a working application toward a production-grade system.
