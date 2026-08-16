# Solar Energy Monitoring Platform — Copilot Instructions

## Project Identity

This repository contains the Solar Energy Monitoring & Asset Management Platform.

The project should be developed as a production-quality, enterprise-oriented application demonstrating senior software engineering practices.

Primary technologies include:

* Angular
* Symfony
* PHP
* MySQL
* Redis
* Docker
* AWS
* REST APIs
* Message queues
* Event-driven processing

---

## General Development Rules

Before modifying code:

1. Inspect the existing implementation.
2. Search for similar functionality.
3. Follow existing project conventions.
4. Reuse existing services, components, repositories and utilities.
5. Avoid unnecessary architectural changes.
6. Avoid modifying unrelated files.
7. Keep changes focused and reviewable.

Do not generate large amounts of code without first understanding the existing architecture.

---

## Architecture Principles

Prefer:

* SOLID
* Separation of concerns
* Dependency injection
* Single responsibility
* Explicit interfaces/contracts
* Small focused services
* Testable components
* Clear domain boundaries
* Consistent error handling
* Production-ready observability

Avoid:

* God classes
* Massive controllers
* Business logic inside controllers
* Business logic directly inside Angular templates
* Duplicated logic
* Hard-coded configuration
* Hidden global state
* Unnecessary abstractions
* Premature optimization

---

## Backend

Backend code should follow the existing Symfony/PHP architecture.

Before creating a new:

* Controller
* Service
* Repository
* Entity
* Event
* Listener
* Message handler

search the repository for an existing equivalent pattern.

Controllers should remain thin.

Business logic should live in appropriate services/domain components.

Use dependency injection rather than manually constructing dependencies.

---

## API

When modifying APIs:

* Preserve existing API conventions.
* Validate incoming requests.
* Use appropriate HTTP status codes.
* Return consistent response structures.
* Handle errors explicitly.
* Avoid leaking internal exceptions.
* Consider authentication and authorization.
* Consider pagination for large datasets.
* Consider filtering and sorting where appropriate.
* Avoid unnecessary database queries.

Do not break existing APIs unless explicitly requested.

---

## Database

MySQL is the primary relational database.

Before changing the database:

1. Inspect existing entities/models.
2. Inspect migrations.
3. Inspect relationships.
4. Inspect indexes.
5. Inspect existing query patterns.

Database changes must use migrations.

Consider:

* Indexes
* Foreign keys
* Uniqueness
* Query performance
* Data growth
* Transaction boundaries

For telemetry-related tables, always consider the expected high write volume.

---

## Telemetry

Telemetry is a core domain of this application.

Telemetry processing should be designed with:

* High throughput
* Reliability
* Idempotency
* Duplicate-event handling
* Retry mechanisms
* Failure handling
* Observability
* Scalability

Avoid unnecessary synchronous processing for high-volume telemetry.

Prefer asynchronous processing when appropriate.

When modifying telemetry processing, inspect:

* Events
* Listeners
* Message handlers
* Queues
* Retry configuration
* Dead-letter handling
* Logging
* Persistence

before implementing changes.

---

## Message Queues

Message processing must be reliable.

Handlers should ideally be:

* Idempotent
* Retry-safe
* Observable
* Failure-aware

Consider:

* Retry count
* Retry delay
* Dead-letter queues
* Poison messages
* Duplicate messages
* Ordering requirements
* Partial failures

Never silently swallow message-processing exceptions.

---

## Redis

Redis may be used for:

* Caching
* Queue infrastructure
* Temporary state
* Distributed locks
* Rate limiting

Redis should not become the primary source of truth unless explicitly required.

Consider:

* TTL
* Cache invalidation
* Memory usage
* Serialization
* Failure behavior

---

## Angular

Follow the existing Angular architecture.

Before creating components/services:

1. Search for similar components.
2. Reuse existing shared components.
3. Reuse existing API services.
4. Follow existing state-management conventions.

Components should remain focused.

Avoid putting substantial business logic inside components.

Handle:

* Loading states
* Empty states
* Error states
* API failures
* Subscription cleanup
* Performance

Avoid unnecessary API requests.

---

## Security

Security must be considered for every feature.

Pay particular attention to:

* Authentication
* Authorization
* Input validation
* SQL injection
* XSS
* CSRF where applicable
* Mass assignment
* File uploads
* API abuse
* Rate limiting
* Sensitive data exposure
* Secret management
* Queue security
* Redis security
* AWS IAM

Never hard-code:

* Passwords
* Tokens
* API keys
* AWS credentials
* Private keys
* Database credentials

Use environment variables or the existing configuration mechanism.

---

## AWS

AWS architecture should prioritize:

* Security
* Reliability
* Scalability
* Observability
* Cost awareness

Before introducing a new AWS service, determine whether an existing component can satisfy the requirement.

Consider:

* IAM
* Networking
* Compute
* Storage
* Database
* Queues
* Monitoring
* Logging
* Scaling
* Disaster recovery

---

## Docker

Follow the existing Docker architecture.

Before modifying Docker configuration inspect:

* Dockerfiles
* docker-compose files
* Environment configuration
* Service dependencies
* Volumes
* Networks

Avoid unnecessarily increasing image size or introducing unnecessary services.

---

## Testing

Meaningful code changes should include appropriate tests.

Prefer:

### Backend

* Unit tests
* Integration tests
* API tests
* Message/queue tests

### Frontend

* Component tests
* Service tests
* Integration tests where appropriate

Tests should verify behavior rather than implementation details.

Do not claim tests passed unless they were actually executed.

---

## Error Handling

Errors must be handled intentionally.

Do not:

* Ignore exceptions
* Return raw internal exceptions
* Hide failures
* Log sensitive information

For operational failures, provide enough logging context to diagnose the problem.

---

## Logging and Observability

Important operations should be observable.

For asynchronous and telemetry workflows, logs should make it possible to determine:

* What happened
* When it happened
* Which operation failed
* Which entity/message was involved
* Whether a retry occurred
* Why processing failed

Avoid logging:

* Passwords
* Tokens
* API keys
* Sensitive personal information
* Secrets

---

## Performance

Always consider performance when working with:

* Telemetry
* Dashboards
* Large datasets
* Database queries
* APIs
* Queues
* Redis
* File processing

Avoid:

* N+1 queries
* Unnecessary API calls
* Loading large datasets into memory
* Unbounded queries
* Unnecessary synchronous processing

---

## Code Changes

For every meaningful implementation, provide a concise summary containing:

### Files Changed

List modified files.

### Files Added

List newly created files.

### Database Changes

Describe migrations/schema changes.

### API Changes

Describe endpoint/request/response changes.

### Queue/Event Changes

Describe asynchronous processing changes.

### Tests

Describe tests added or modified.

### Verification

List the commands/tests actually executed.

Never claim a command was executed if it was not.

---

## Documentation

When introducing significant architecture or behavior changes, update the relevant documentation.

Documentation should explain:

* Why the feature exists
* How it works
* Important design decisions
* Configuration requirements
* Operational considerations

Avoid documenting obvious implementation details.

---

## Git Hygiene

Do not modify:

* `.env`
* Secret files
* Generated files

unless explicitly required.

Do not make unrelated formatting changes.

Keep commits logically focused.

Suggested commit style:

```text
feat: add telemetry ingestion
fix: handle failed telemetry messages
refactor: simplify asset service
test: add telemetry handler tests
docs: document telemetry architecture
```

---

## Implementation Workflow

For non-trivial tasks use this workflow:

### 1. Understand

Inspect the repository and relevant code.

### 2. Plan

Explain the implementation approach.

### 3. Implement

Make focused changes.

### 4. Test

Run relevant tests.

### 5. Review

Check:

* Correctness
* Security
* Performance
* Maintainability
* Scalability

### 6. Summarize

Explain exactly what changed.

---

## Most Important Rule

Do not assume the architecture.

Inspect the repository first.

Prefer existing patterns over new patterns.

Keep the implementation simple unless the requirements justify additional complexity.

Production quality is more important than generating large amounts of code.
