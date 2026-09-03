# Security Engineering Standards

## 1. Purpose

This document defines the security engineering standards for the Solar Energy Monitoring & Asset Management Platform.

Security must be treated as a system-wide engineering responsibility rather than a deployment-only concern.

These standards apply to:

- Backend services
- Frontend applications
- REST APIs
- Databases
- Telemetry pipelines
- Queues
- Background workers
- Dead Letter Queues
- Containers
- Infrastructure
- CI/CD pipelines
- AWS resources
- Operational tooling

---

## 2. Security Principles

The platform follows these core security principles:

1. Defense in depth
2. Least privilege
3. Secure by default
4. Fail closed
5. Explicit authorization
6. Zero trust between components
7. Minimize sensitive data
8. Encrypt sensitive data
9. Validate untrusted input
10. Never trust client-controlled authorization data
11. Avoid secrets in source code
12. Make security failures observable
13. Prefer short-lived credentials
14. Separate environments
15. Assume infrastructure components can fail or be compromised

---

## 3. Authentication

All protected APIs must require authenticated access.

Authentication responsibilities include:

- Verify the caller identity.
- Validate authentication credentials.
- Reject expired credentials.
- Reject malformed credentials.
- Avoid accepting identity information directly from request payloads.
- Never trust a client-provided user ID as proof of identity.

Authentication failures must return an appropriate unauthorized response without exposing sensitive implementation details.

---

## 4. Authorization

Authentication answers:

> Who is the caller?

Authorization answers:

> Is the caller allowed to perform this operation?

Every protected operation must perform authorization independently.

Authorization must not rely solely on:

- Frontend route guards
- Hidden UI elements
- Client-provided role values
- Client-provided tenant IDs
- Client-side permissions

Authorization must be enforced server-side.

---

## 5. Role-Based Access Control

The platform should use explicit role-based authorization.

Typical roles may include:

- Platform Administrator
- Organization Administrator
- Operations User
- Maintenance User
- Read-Only User

Permissions should be defined around capabilities rather than hard-coded throughout controllers.

Example capabilities:

- View assets
- Create assets
- Update assets
- Delete assets
- View telemetry
- Replay DLQ events
- Manage users
- View operational metrics

---

## 6. Tenant Isolation

Tenant isolation is a critical security boundary.

Every tenant-scoped operation must enforce tenant ownership on the server.

A request must never be allowed to access another tenant's:

- Assets
- Devices
- Telemetry
- Users
- Events
- DLQ records
- Reports
- Operational data

Tenant identifiers supplied by clients must not automatically be trusted.

The authenticated identity and server-side authorization context must determine the effective tenant.

---

## 7. API Security

All protected APIs must enforce:

- Authentication
- Authorization
- Input validation
- Rate limiting where appropriate
- Request size limits
- Content-type validation
- Secure error handling
- Audit logging for sensitive operations

APIs should reject unexpected input rather than silently accepting arbitrary fields.

---

## 8. Input Validation

All external input is untrusted.

Validation must occur at the application boundary.

Validate:

- Data types
- Required fields
- Maximum lengths
- Allowed values
- UUID formats
- Date formats
- Numeric ranges
- Array sizes
- Nested object structures
- Payload sizes

Telemetry ingestion must additionally enforce:

- Maximum events per batch
- Valid event identifiers
- Valid timestamps
- Valid schema versions
- Valid event types
- Expected payload structure

---

## 9. Mass Assignment Protection

Models must not blindly accept arbitrary request fields.

Applications must explicitly define:

- Allowed fields
- Validated fields
- Writable fields

Sensitive fields such as:

- tenant_id
- role
- permissions
- ownership
- status
- audit fields

must not be writable solely because they appear in a request payload.

---

## 10. SQL Injection Protection

Database queries must use parameterized query mechanisms.

Application code must avoid constructing SQL statements directly from user-controlled input.

Preferred mechanisms include:

- Laravel query builder
- Eloquent ORM
- Parameterized queries

Dynamic SQL must receive explicit security review.

---

## 11. Cross-Site Scripting Protection

User-controlled data must not be rendered as trusted HTML.

Frontend applications must:

- Escape dynamic content.
- Avoid unsafe HTML rendering.
- Sanitize content when HTML is genuinely required.
- Avoid inserting untrusted strings directly into DOM APIs.

Backend responses must not assume frontend consumers will safely handle arbitrary HTML.

---

## 12. Cross-Site Request Forgery

State-changing browser operations must use appropriate CSRF protection where cookie-based authentication is used.

The application must not disable CSRF protections merely to simplify frontend integration.

API authentication mechanisms must be selected consistently with the application's trust model.

---

## 13. Cross-Origin Resource Sharing

CORS must follow an allow-list model.

Production environments must not use unrestricted origins unless there is an explicitly documented architectural reason.

CORS configuration must define:

- Allowed origins
- Allowed methods
- Allowed headers
- Credential behavior

Development and production CORS configurations must be separated.

---

## 14. Rate Limiting

Public and sensitive endpoints should use rate limiting.

Rate limiting is particularly important for:

- Authentication endpoints
- Password/credential operations
- Telemetry ingestion
- Administrative APIs
- DLQ replay endpoints
- Resource-intensive APIs

Limits should consider:

- Client identity
- Tenant
- Endpoint
- Request volume
- Payload size

---

## 15. Request Size Limits

Applications must limit request sizes.

Telemetry ingestion must enforce the documented maximum batch size.

Large payloads must not be allowed to consume disproportionate:

- Memory
- CPU
- Database capacity
- Queue capacity

Request size limits should exist at multiple layers where appropriate:

- ALB
- Nginx
- Application
- Queue
- Database

---

## 16. Secrets Management

Secrets must never be committed to source control.

Never store:

- Database passwords
- API keys
- AWS access keys
- Private keys
- Tokens
- Encryption keys
- Production credentials

inside Git repositories.

Secrets should be supplied through appropriate secret-management mechanisms.

For AWS workloads, use:

- AWS Secrets Manager
- IAM roles
- Short-lived credentials

where appropriate.

---

## 17. Environment Separation

Development, staging, and production environments must be isolated.

Production credentials must never be reused in development.

Production databases must not be used for local development.

Environment-specific configuration must be externalized.

---

## 18. Encryption in Transit

Sensitive communication must use encrypted transport.

Production traffic should use:

- HTTPS
- TLS
- Encrypted database connections where supported
- TLS-protected Redis connections where configured
- Secure AWS service communication

Plaintext production credentials or sensitive payloads must not traverse untrusted networks.

---

## 19. Encryption at Rest

Sensitive persistent data must use encryption at rest where supported.

This includes:

- RDS databases
- Redis
- Object storage
- Secrets
- Backup storage
- Container/image registries where applicable

Encryption keys and access to encrypted resources must be protected using appropriate AWS controls.

---

## 20. Database Security

Databases must not be directly exposed to the public internet.

Database access should be restricted using:

- Private subnets
- Security groups
- Application-level credentials
- Least-privilege database users

Applications should use separate credentials according to environment and workload requirements.

---

## 21. Database Credentials

Application services must not use database administrator credentials for normal application operations.

Database permissions should be limited to the operations required by the application.

Migration privileges should be separated from runtime privileges where practical.

---

## 22. Queue Security

Queue messages must be treated as untrusted internal input.

Consumers must validate:

- Event type
- Schema version
- Event ID
- Required metadata
- Payload structure

Queue workers must not assume that messages were generated correctly merely because they originated from an internal service.

---

## 23. Telemetry Security

Telemetry payloads must be validated before processing.

Telemetry processing must protect against:

- Malformed payloads
- Oversized payloads
- Unexpected fields
- Invalid timestamps
- Invalid identifiers
- Malicious nested data
- Excessive batch sizes

Telemetry must not be used as an unrestricted mechanism for executing application commands.

---

## 24. Event Identity Security

Event identifiers must be validated.

The system must not allow clients to use event IDs to bypass authorization or tenant isolation.

Idempotency provides duplicate protection; it does not provide authorization.

These concerns must remain separate.

---

## 25. DLQ Security

DLQ records may contain original event payloads and failure information.

Therefore DLQ access must be restricted.

DLQ operations such as:

- View
- Search
- Replay
- Delete

should require appropriate authorization.

Replay must never be treated as an ordinary user operation.

---

## 26. DLQ Replay Authorization

DLQ replay must require elevated permissions.

Replay operations should record:

- Actor
- Event ID
- Time
- Reason
- Result

Unauthorized users must not be able to replay arbitrary events.

---

## 27. Replay Safety

Replay operations must preserve:

- Idempotency
- Tenant isolation
- Event identity
- Schema validation
- Authorization
- Observability

Replay must not bypass normal processing security controls.

---

## 28. Logging Security

Logs must never contain sensitive secrets.

Do not log:

- Passwords
- Access tokens
- API keys
- Private keys
- Session secrets
- Database credentials

Sensitive payload fields should be masked or excluded.

---

## 29. Error Message Security

Production errors must not expose:

- Stack traces
- Database credentials
- SQL statements
- Internal file paths
- Secret values
- Infrastructure credentials
- Internal authentication details

Clients should receive safe, actionable error responses.

Detailed diagnostics should remain in controlled server-side logs.

---

## 30. Audit Logging

Security-sensitive operations should produce audit records.

Examples include:

- Login
- Logout
- Permission changes
- User creation
- User deletion
- Asset ownership changes
- DLQ replay
- Administrative configuration changes

Audit records should capture sufficient information for investigation without storing unnecessary sensitive data.

---

## 31. Correlation and Traceability

Requests and asynchronous operations should support correlation identifiers.

Correlation data should allow operators to trace:

Request → API → Queue → Worker → Database → DLQ

Correlation identifiers must not themselves contain secrets or sensitive information.

---

## 32. Dependency Security

Dependencies must be kept current according to the project's dependency-management standards.

Security vulnerabilities must be evaluated based on:

- Severity
- Exploitability
- Exposure
- Runtime usage
- Availability of fixes

Critical vulnerabilities affecting production attack surfaces require immediate attention.

---

## 33. Container Security

Container images must:

- Use trusted base images.
- Minimize installed packages.
- Avoid unnecessary tools.
- Run as non-root where practical.
- Avoid embedded secrets.
- Be scanned for vulnerabilities.
- Use immutable production image tags.

Production deployments should use known image versions rather than mutable tags such as `latest`.

---

## 34. Docker Build Security

Docker builds must avoid copying unnecessary repository content into runtime images.

Do not include:

- `.env`
- credentials
- SSH keys
- local development secrets
- unnecessary source artifacts

`.dockerignore` must be maintained appropriately.

---

## 35. IAM Least Privilege

AWS identities must receive only the permissions required for their function.

Separate permissions should exist for:

- ECS execution
- ECS application tasks
- CI/CD deployment
- Terraform infrastructure management

Wildcard permissions should require justification.

---

## 36. IAM Role Separation

Runtime workloads should use IAM roles rather than long-lived AWS access keys.

CI/CD should use GitHub OIDC where supported.

Application containers should not contain static AWS credentials.

---

## 37. CI/CD Security

CI/CD pipelines must protect:

- Repository credentials
- Deployment permissions
- AWS permissions
- Build artifacts
- Production environments

Production deployments should require:

- Protected branch controls
- Environment protection
- Appropriate approval controls
- Short-lived cloud credentials

---

## 38. GitHub OIDC Security

GitHub Actions OIDC trust policies must restrict:

- Repository
- Branch
- Environment
- Intended deployment role

OIDC trust must not be broader than required.

Production deployment roles should not be usable by arbitrary branches or pull requests.

---

## 39. Infrastructure Security

Infrastructure must follow secure-by-default principles.

Production architecture should prefer:

- Private databases
- Restricted security groups
- No unnecessary public IPs
- Least-privilege IAM
- Encrypted storage
- Controlled ingress
- Controlled egress

---

## 40. Network Security

Network access should follow explicit allow rules.

Security groups must avoid unrestricted access where unnecessary.

Examples of controls:

- ALB accepts public HTTP/HTTPS traffic as required.
- ECS accepts application traffic only from the ALB.
- Databases accept traffic only from authorized application workloads.
- Redis accepts traffic only from authorized application workloads.

---

## 41. SSRF Protection

Server-side requests must not blindly follow user-controlled URLs.

When external URLs are accepted:

- Validate schemes.
- Validate hosts.
- Restrict destinations where possible.
- Block access to cloud metadata endpoints.
- Prevent internal network probing.

---

## 42. File Upload Security

If file uploads are introduced, uploaded files must be treated as untrusted.

Controls should include:

- File size limits
- MIME validation
- Extension validation
- Content inspection
- Safe storage
- Randomized filenames
- Malware scanning where appropriate
- No direct execution from upload directories

---

## 43. Command Injection Protection

Application code must not execute shell commands using untrusted user input.

If operating-system commands are required:

- Avoid shell interpretation where possible.
- Use strict argument validation.
- Use allow-lists.
- Never concatenate raw user input into shell commands.

---

## 44. Deserialization Security

Untrusted serialized data must not be deserialized using unsafe mechanisms.

Message and API payloads should use controlled formats such as JSON with explicit schemas.

---

## 45. Security Headers

Production HTTP responses should use appropriate security headers.

Depending on application requirements, these may include:

- Content-Security-Policy
- Strict-Transport-Security
- X-Content-Type-Options
- Referrer-Policy
- Frame protection

Headers must be tested against actual frontend behavior before enforcing restrictive policies.

---

## 46. Session Security

Where sessions are used:

- Use secure cookies.
- Use HttpOnly cookies where appropriate.
- Apply appropriate SameSite settings.
- Expire inactive sessions.
- Regenerate session identifiers after authentication changes.

Session secrets must never be logged.

---

## 47. Password Security

Passwords must never be stored in plaintext.

Password storage must use modern password hashing mechanisms supported by the framework.

Applications must not implement custom password hashing algorithms.

---

## 48. Sensitive Data Minimization

Only collect and store information required for the business purpose.

Avoid storing sensitive information unnecessarily in:

- Database records
- Logs
- Events
- Queue messages
- DLQ payloads
- Analytics systems

Data minimization reduces both security risk and operational cost.

---

## 49. Data Retention

Security-sensitive data should have explicit retention policies.

Retention must consider:

- Business requirements
- Compliance requirements
- Operational debugging
- Security investigations
- Storage cost

Expired data should be removed or archived according to policy.

---

## 50. Security Testing

Security testing should be integrated into the engineering lifecycle.

Testing should include:

- Authentication tests
- Authorization tests
- Tenant-isolation tests
- Input-validation tests
- API security tests
- Dependency scanning
- Container scanning
- Infrastructure security validation
- Negative tests
- Abuse-case tests

---

## 51. Authorization Testing

Tests must verify both positive and negative authorization cases.

Examples:

- Authorized user can access permitted resource.
- Unauthorized user cannot access resource.
- User from Tenant A cannot access Tenant B data.
- Read-only user cannot perform write operations.
- Normal user cannot replay DLQ events.

---

## 52. Security Regression Testing

Security fixes must include regression tests where practical.

A vulnerability that has once occurred should not silently reappear during future refactoring.

Security-sensitive behavior should therefore be represented in automated tests.

---

## 53. Security Failure Behavior

Security failures must fail closed.

Examples:

- Authorization lookup unavailable → deny access.
- Tenant context unavailable → reject request.
- Required security configuration missing → fail startup where appropriate.
- Invalid credentials → reject request.
- Invalid event authorization context → reject processing.

Security controls must not silently fall back to permissive behavior.

---

## 54. Secrets Rotation

Secrets should support rotation without requiring unnecessary application redesign.

Applications should avoid assumptions that credentials remain valid indefinitely.

Rotation procedures should be documented for:

- Database credentials
- API credentials
- Encryption keys
- Third-party integrations

---

## 55. Security Monitoring

Security-relevant signals should be observable.

Monitor for:

- Authentication failures
- Authorization failures
- Unusual API activity
- Excessive request rates
- Repeated DLQ replay failures
- Suspicious administrative actions
- Dependency vulnerabilities
- Container vulnerabilities
- IAM anomalies
- Infrastructure configuration changes

---

## 56. Security Incident Response

Security incidents must follow the production incident-response process.

The response should include:

1. Detect
2. Validate
3. Contain
4. Investigate
5. Remediate
6. Recover
7. Review

Security incidents should result in appropriate follow-up actions, including preventive improvements.

---

## 57. Security Review Checklist

Before production release, verify:

- [ ] Authentication enforced
- [ ] Authorization enforced
- [ ] Tenant isolation verified
- [ ] Input validation implemented
- [ ] Rate limiting reviewed
- [ ] Request-size limits configured
- [ ] Secrets removed from source
- [ ] Secrets supplied securely
- [ ] TLS enabled where required
- [ ] Databases private
- [ ] IAM follows least privilege
- [ ] OIDC trust restricted
- [ ] Container images scanned
- [ ] Dependencies scanned
- [ ] Sensitive logs reviewed
- [ ] Error responses reviewed
- [ ] DLQ access protected
- [ ] DLQ replay authorization enforced
- [ ] Security regression tests pass
- [ ] Security monitoring configured

---

## 58. Engineering Principle

Security is not a separate phase after implementation.

Every feature must consider:

**Identity → Authorization → Input → Data → Communication → Execution → Logging → Monitoring**

A feature is not production-ready until its security boundaries are explicitly understood and validated.
