# Coding and Design Standards

## 1. Purpose

This document defines coding and software design standards for the Solar Energy Monitoring Platform.

The objective is to maintain:

- readable code
- predictable structure
- low coupling
- high cohesion
- testability
- maintainability
- consistent implementation
- safe architectural evolution

These standards apply to application code, background jobs, events, services, APIs, and supporting components.

---

## 2. General Principles

Code should follow:

- Single Responsibility Principle
- Open/Closed Principle
- Liskov Substitution Principle
- Interface Segregation Principle
- Dependency Inversion Principle

Code should favor:

- composition over unnecessary inheritance
- explicit dependencies
- small focused classes
- small focused methods
- clear naming
- predictable behavior

---

## 3. Controller Responsibilities

Controllers should coordinate HTTP requests.

A controller should primarily:

1. receive the request
2. validate input through Form Requests
3. call the appropriate application service
4. return the appropriate response

Controllers should not contain:

- complex business rules
- large database queries
- retry logic
- queue processing logic
- complex transformations
- infrastructure-specific behavior

Example:

    Controller
       ↓
    Request Validation
       ↓
    Application Service
       ↓
    Domain / Persistence

---

## 4. Service Layer

Services contain application-level business orchestration.

A service may:

- coordinate multiple models
- execute business workflows
- manage transactions
- dispatch jobs
- publish events
- enforce application rules

Services should avoid becoming large "god classes".

If a service contains unrelated responsibilities, split the functionality into focused components.

---

## 5. Models

Models should represent persistence and domain-related behavior.

Models may contain:

- relationships
- casts
- scopes
- persistence-related behavior
- simple domain rules

Models should not become containers for unrelated application workflows.

Complex workflows belong in services or dedicated domain components.

---

## 6. Form Request Validation

HTTP validation should use dedicated Form Request classes.

Example:

    TelemetryEventRequest

Validation should happen before business processing.

Benefits:

- reusable validation
- cleaner controllers
- consistent error responses
- easier testing

Controllers should not contain large inline validation definitions when a dedicated request object is appropriate.

---

## 7. DTOs

Data Transfer Objects should be introduced when data crosses application boundaries and requires a stable structure.

DTOs are particularly useful for:

- complex service inputs
- external integrations
- event payloads
- command objects
- internal application boundaries

DTOs should represent data clearly and avoid embedding unrelated business behavior.

---

## 8. Repository Pattern

Repositories should not be introduced automatically for every model.

Direct Eloquent usage is acceptable for straightforward persistence operations.

Repositories may be appropriate when:

- persistence logic is complex
- multiple data sources exist
- a stable abstraction is required
- testing requires a meaningful persistence boundary
- the domain needs to hide infrastructure details

Avoid creating interfaces and repositories that merely duplicate Eloquent methods without providing meaningful abstraction.

---

## 9. Dependency Injection

Dependencies should be injected rather than created deep inside business logic.

Prefer:

    Constructor Injection

over:

    new Dependency()

inside methods.

Benefits:

- testability
- loose coupling
- explicit dependencies
- easier replacement

Laravel's service container should be used where appropriate.

---

## 10. Naming Standards

Names should communicate intent.

### Classes

Use PascalCase.

Examples:

    TelemetryService
    ProcessTelemetryBatchJob
    TelemetryEventRequest

### Methods

Use camelCase.

Examples:

    ingest()
    processBatch()
    replay()

### Variables

Use descriptive camelCase names.

Example:

    $telemetryEvents

Avoid:

    $data
    $obj
    $tmp

when a more meaningful name exists.

---

## 11. Boolean Naming

Boolean variables should clearly indicate state.

Prefer:

    $isActive
    $hasAccess
    $shouldRetry
    $isProcessed

Avoid ambiguous names such as:

    $active
    $flag
    $status

when the variable represents a boolean.

---

## 12. Constants and Enums

Repeated fixed values should not be scattered throughout the codebase.

Use:

- constants
- enums
- configuration

where appropriate.

Examples:

    DeadLetterStatus

Enums should represent finite domain states clearly.

---

## 13. Configuration

Environment-specific configuration belongs in configuration files and environment variables.

Avoid:

    if ($environment === 'production') {
        // completely different business behavior
    }

when configuration can express the difference.

Secrets must never be hardcoded.

---

## 14. Exception Handling

Exceptions should represent exceptional conditions.

Do not use exceptions for normal control flow.

Exceptions should:

- provide meaningful context
- be logged appropriately
- avoid leaking sensitive information
- map to appropriate API responses

Do not expose internal stack traces in production API responses.

---

## 15. Error Handling

Application errors should be predictable.

API responses should distinguish between:

- validation errors
- authentication failures
- authorization failures
- resource-not-found errors
- business rule violations
- infrastructure failures
- unexpected exceptions

Internal infrastructure details should not be exposed to API consumers.

---

## 16. Transactions

Database transactions should surround operations that must succeed or fail together.

Example:

    Begin Transaction
        ↓
    Update Asset
        ↓
    Create Audit Record
        ↓
    Commit

If a critical step fails:

    Rollback

Transactions should remain as small as practical.

Avoid holding long-running external operations inside database transactions.

---

## 17. Database Access

Database access should be deliberate.

Avoid:

- N+1 queries
- unnecessary SELECT *
- queries inside large loops
- repeated identical queries
- unbounded result sets

Prefer:

- eager loading
- pagination
- appropriate indexes
- batch operations
- explicit selected columns where useful

Database performance should be validated with realistic data volumes.

---

## 18. Queue Job Standards

Queue jobs should be:

- idempotent where possible
- retry-aware
- bounded by timeouts
- observable
- safe to execute more than once

Jobs should define appropriate:

- retry count
- timeout
- backoff strategy
- failure behavior

Long-running jobs should not perform unnecessary work synchronously.

---

## 19. Telemetry Job Standards

Telemetry processing must preserve:

- event identity
- idempotency
- retry state
- failure reason
- attempt count

A telemetry job should never assume that it will execute exactly once.

The processing model is:

    At-Least-Once Delivery
             +
        Idempotent Processing

---

## 20. Event Standards

Events should contain enough information for consumers to process them independently.

Where appropriate, events should include:

- event ID
- entity ID
- event type
- event timestamp
- schema version
- correlation information
- relevant payload

Events should avoid unnecessary sensitive data.

---

## 21. Event Versioning

Event contracts should evolve backward-compatibly.

When an event schema changes:

- introduce a schema version
- maintain compatibility where possible
- update consumers safely
- document breaking changes

Never silently change the meaning of an existing event field.

---

## 22. Logging Standards

Application logs should be structured and useful.

Include identifiers such as:

- request ID
- event ID
- tenant ID
- source ID
- job ID

Logs should help answer:

- what happened?
- where did it happen?
- when did it happen?
- which request/event caused it?
- what was the result?

Never log:

- passwords
- access tokens
- secret keys
- database credentials
- sensitive personal information

---

## 23. Testing Standards

Code should be tested at the appropriate level.

### Unit Tests

Use for:

- isolated business logic
- calculations
- transformations
- pure functions

### Feature Tests

Use for:

- API behavior
- authentication
- database interactions
- queue dispatch
- complete application workflows

### Integration Tests

Use for:

- external dependencies
- infrastructure boundaries
- database behavior where required

Testing should focus on behavior rather than implementation details.

---

## 24. Telemetry Testing

Telemetry functionality should test:

- valid ingestion
- invalid payloads
- batch limits
- duplicate events
- asynchronous dispatch
- retries
- retry exhaustion
- DLQ behavior
- replay
- idempotency

Failure paths are first-class test scenarios.

---

## 25. Test Naming

Test names should describe expected behavior.

Prefer:

    test_duplicate_telemetry_event_is_ignored

over:

    testTelemetry1

A test should communicate what behavior it protects.

---

## 26. Code Review Standards

Every significant change should be reviewed for:

- correctness
- security
- maintainability
- performance
- test coverage
- observability
- failure handling
- backward compatibility

Reviewers should question architectural impact, not only syntax.

---

## 27. Pull Request Standards

Pull requests should clearly describe:

- problem
- proposed solution
- architectural impact
- testing performed
- migration requirements
- deployment considerations
- rollback considerations

Large unrelated changes should not be bundled into the same pull request.

---

## 28. Method Size

Methods should remain focused.

If a method performs:

- validation
- transformation
- persistence
- notification
- logging
- retry handling

all at once, consider decomposing it.

The goal is not an arbitrary line limit.

The goal is a method with one clear responsibility.

---

## 29. Class Size

Classes should have cohesive responsibilities.

A class that handles:

- HTTP
- database access
- queue processing
- notifications
- reporting

should normally be split.

Large classes should trigger architectural review.

---

## 30. Avoid Premature Abstraction

Do not introduce abstractions without a real need.

Avoid:

- unnecessary interfaces
- unnecessary repositories
- unnecessary factories
- unnecessary inheritance hierarchies
- excessive generic frameworks

Prefer simple code until a real architectural boundary emerges.

---

## 31. Avoid God Objects

A god object accumulates unrelated responsibilities.

Warning signs include:

- many unrelated methods
- excessive dependencies
- difficult testing
- frequent changes for unrelated features
- unclear ownership

When detected:

1. identify responsibilities
2. group related behavior
3. extract focused components
4. reduce coupling

---

## 32. Avoid Hidden Side Effects

Methods should have predictable behavior.

A method named:

    getTelemetry()

should not unexpectedly:

- modify records
- dispatch jobs
- send notifications

Side effects should be explicit in naming or architecture.

---

## 33. API Boundary Discipline

Application internals should not be unnecessarily exposed through API responses.

API resources should define the external contract.

Avoid returning internal database models directly when the API contract requires a stable representation.

Use:

- API Resources
- DTOs
- response objects

where appropriate.

---

## 34. Pagination

Collection APIs should use pagination for potentially large datasets.

Avoid returning unbounded database results.

Pagination should define:

- page size
- maximum page size
- current page or cursor
- total information where appropriate

Maximum limits protect the system from accidental large queries.

---

## 35. Input Validation

Never trust external input.

Validate:

- type
- format
- required fields
- allowed values
- length
- relationships
- business constraints

Validation must occur before expensive processing.

---

## 36. Authorization

Authentication answers:

> Who are you?

Authorization answers:

> What are you allowed to do?

Every protected operation must enforce authorization.

Do not rely only on frontend restrictions.

---

## 37. Performance Standards

Performance optimization should be measurement-driven.

Before optimizing:

1. measure
2. identify bottleneck
3. change implementation
4. measure again

Avoid premature optimization based only on assumptions.

---

## 38. Concurrency Safety

Code that can execute concurrently must be designed accordingly.

Examples:

- duplicate telemetry requests
- queue retries
- concurrent workers
- scheduled jobs
- DLQ replay

Use appropriate:

- unique constraints
- transactions
- locks where necessary
- idempotency
- atomic operations

---

## 39. Time and Date Handling

Use consistent timezone handling.

Recommended approach:

- store timestamps consistently
- use UTC for backend persistence and event timestamps where appropriate
- convert to user timezone at presentation boundaries

Never rely on server-local timezone behavior implicitly.

---

## 40. Security During Development

Developers must:

- avoid committing secrets
- avoid disabling security controls permanently
- validate untrusted input
- use least privilege
- review dependency vulnerabilities
- avoid exposing internal errors

Development shortcuts must not silently become production configuration.

---

## 41. Maintainability Standard

Prefer code that another engineer can understand without requiring the original author.

Good code should have:

- meaningful names
- clear boundaries
- predictable flow
- limited coupling
- appropriate documentation

Comments should explain why something exists, not merely repeat what the code does.

---

## 42. Documentation Standard

Architecturally significant behavior should be documented.

Examples:

- retry policies
- DLQ behavior
- event contracts
- database migration strategy
- deployment behavior
- security decisions
- scaling decisions

Documentation should live close to the code or architecture documentation.

---

## 43. Refactoring Standard

Refactoring should preserve behavior unless behavior change is intentional.

A refactoring pull request should:

- maintain existing tests
- avoid unrelated feature changes
- explain architectural improvements
- identify performance implications where relevant

Large refactors should be broken into manageable steps.

---

## 44. Backward Compatibility

Changes to shared interfaces should consider existing consumers.

Before changing:

- API fields
- event schemas
- database contracts
- configuration
- queue payloads

identify affected consumers.

Breaking changes require explicit architectural review.

---

## 45. Coding Standard Checklist

Before merging code, verify:

- Is the responsibility clear?
- Is the naming meaningful?
- Are dependencies explicit?
- Is validation handled correctly?
- Are database queries efficient?
- Is error handling appropriate?
- Is the code testable?
- Are failure scenarios covered?
- Is the code observable?
- Are security implications understood?
- Is backward compatibility preserved?

---

## 46. Engineering Principle

The coding standard can be summarized as:

> Prefer simple, explicit, testable, secure, and maintainable code with clear boundaries and minimal unnecessary abstraction.

Code quality is not measured by complexity.

It is measured by how safely and predictably the system can evolve.
