# REST API Design Standards

## 1. Purpose

This document defines REST API standards for the Solar Energy Monitoring Platform.

The objective is to provide APIs that are:

- consistent
- predictable
- secure
- versioned
- observable
- backward-compatible
- scalable
- easy for consumers to integrate with

These standards apply to all externally exposed REST APIs.

---

## 2. API Design Principles

APIs should follow these principles:

1. APIs are contracts.
2. Resource names should represent business resources.
3. HTTP methods should have predictable semantics.
4. Responses should use consistent structures.
5. Validation should happen at the API boundary.
6. Errors should be predictable.
7. APIs should be backward-compatible where possible.
8. APIs should be observable.
9. APIs should enforce authentication and authorization.
10. APIs should protect the platform from excessive traffic.

---

## 3. API Versioning

APIs should use explicit versions.

The current platform convention is:

    /api/v1/...

Example:

    /api/v1/telemetry/events

Versioning allows future API contracts to evolve without immediately breaking existing consumers.

A breaking contract change should normally require a new API version.

---

## 4. Resource Naming

Use nouns rather than verbs for resource endpoints.

Prefer:

    /api/v1/assets
    /api/v1/devices
    /api/v1/telemetry/events

Avoid:

    /api/v1/getAssets
    /api/v1/createDevice
    /api/v1/processTelemetry

HTTP methods communicate the intended operation.

---

## 5. HTTP Methods

Use HTTP methods consistently.

### GET

Retrieve resources.

Example:

    GET /api/v1/assets

### POST

Create a resource or initiate an operation that is not naturally idempotent.

Example:

    POST /api/v1/assets

### PUT

Replace a resource or perform an idempotent update.

Example:

    PUT /api/v1/assets/{assetId}

### PATCH

Partially update a resource.

Example:

    PATCH /api/v1/assets/{assetId}

### DELETE

Remove a resource where deletion is supported.

Example:

    DELETE /api/v1/assets/{assetId}

---

## 6. Resource Hierarchies

Use nested resources only when the relationship is meaningful.

Example:

    /api/v1/assets/{assetId}/devices

Avoid deeply nested paths.

For example, avoid:

    /api/v1/companies/{companyId}/sites/{siteId}/assets/{assetId}/devices/{deviceId}/telemetry

Deep nesting makes APIs difficult to consume and evolve.

---

## 7. HTTP Status Codes

APIs should use standard HTTP status codes.

### 200 OK

Successful request returning a response.

### 201 Created

Resource successfully created.

### 202 Accepted

Request accepted for asynchronous processing.

The telemetry ingestion API uses this model because ingestion can be asynchronous.

### 204 No Content

Successful operation without a response body.

### 400 Bad Request

Malformed request.

### 401 Unauthorized

Authentication is missing or invalid.

### 403 Forbidden

Authenticated user does not have permission.

### 404 Not Found

Requested resource does not exist.

### 409 Conflict

Request conflicts with current resource state.

Useful for certain duplicate or state-conflict scenarios.

### 422 Unprocessable Entity

Validation failure.

### 429 Too Many Requests

Rate limit exceeded.

### 500 Internal Server Error

Unexpected server-side failure.

---

## 8. Response Consistency

Responses should use consistent structures across APIs.

Successful collection responses should clearly represent:

- data
- pagination information where applicable
- metadata where required

Example:

    {
      "data": [...]
    }

For paginated responses:

    {
      "data": [...],
      "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 100
      }
    }

The exact response contract should be documented per API.

---

## 9. Error Response Standard

Error responses should provide useful information without exposing internal implementation details.

Example:

    {
      "error": {
        "code": "VALIDATION_ERROR",
        "message": "The request contains invalid fields",
        "details": {
          "events": [
            "The events field is required."
          ]
        }
      }
    }

Errors should have stable machine-readable codes where appropriate.

---

## 10. Internal Error Protection

Production APIs must not expose:

- stack traces
- database credentials
- SQL statements
- internal file paths
- infrastructure secrets
- internal service credentials

Internal details should be logged securely for operators.

Consumers should receive safe error messages.

---

## 11. Validation

All external input must be validated.

Validation should cover:

- required fields
- data types
- formats
- lengths
- allowed values
- relationships
- business constraints

Validation should happen before expensive processing.

Laravel Form Requests should be preferred for complex request validation.

---

## 12. Authentication

Protected APIs must authenticate callers.

Authentication mechanisms may include:

- tokens
- OAuth/OIDC
- service credentials
- platform-specific authentication mechanisms

Authentication should happen before protected business operations are executed.

---

## 13. Authorization

Authentication does not imply authorization.

Every protected operation must verify:

- user permissions
- tenant access
- resource ownership
- role
- scope

Authorization must be enforced on the backend.

Frontend visibility rules must never be considered sufficient authorization.

---

## 14. Multi-Tenant Isolation

Tenant boundaries must be enforced server-side.

Every tenant-scoped request should verify that the caller is allowed to access the requested tenant's data.

A user must never be able to access another tenant's resources by changing:

    tenant_id

in a request.

Tenant authorization should be applied consistently across:

- APIs
- services
- jobs
- database queries
- background processing

---

## 15. Idempotency

Operations that may be retried should define an idempotency strategy.

Telemetry ingestion uses:

    event_id

as the event identity.

The system protects idempotency using:

- unique event identifiers
- database uniqueness constraints
- safe duplicate handling
- retry compatibility
- replay compatibility

A repeated request must not unintentionally create duplicate business data.

---

## 16. Request IDs

Every API request should have a correlation identifier.

Example:

    X-Request-ID

If the client provides a valid request ID, the platform may preserve it according to the request-ID policy.

Otherwise, the platform should generate one.

The request ID should appear in:

- application logs
- error logs
- relevant downstream operations

This allows operators to trace a request across the system.

---

## 17. Event Correlation

Telemetry processing should preserve event identity.

Important identifiers include:

- Request ID
- Event ID
- Tenant ID
- Source ID
- Job ID

Example:

    API Request
        ↓
    Request ID
        ↓
    Telemetry Event
        ↓
    Event ID
        ↓
    Queue Job
        ↓
    Job ID
        ↓
    Database

These identifiers make asynchronous failures easier to investigate.

---

## 18. Pagination

Collection APIs should not return unbounded datasets.

Pagination should define:

- default page size
- maximum page size
- current page or cursor
- total information where appropriate

Example:

    GET /api/v1/assets?page=1&per_page=20

The server must enforce a maximum page size.

---

## 19. Cursor Pagination

Cursor pagination may be preferred for very large or frequently changing datasets.

Example:

    GET /api/v1/telemetry/events?cursor=<cursor>

Cursor pagination can avoid some performance problems associated with large offset values.

The appropriate pagination mechanism depends on the API workload.

---

## 20. Filtering

Filtering should use query parameters.

Example:

    GET /api/v1/assets?status=active

For telemetry:

    GET /api/v1/telemetry/events?source_id=123

Filtering parameters should be documented and validated.

---

## 21. Sorting

Sorting should use explicit query parameters.

Example:

    GET /api/v1/assets?sort=created_at

Descending order may use a documented convention such as:

    GET /api/v1/assets?sort=-created_at

Only approved sortable fields should be accepted.

---

## 22. Search

Search parameters should have predictable semantics.

Example:

    GET /api/v1/assets?search=block-a

Search should be implemented using appropriate database indexes or search infrastructure.

Do not allow unrestricted arbitrary database expressions through query parameters.

---

## 23. Bulk APIs

High-volume operations may use bulk endpoints.

Example:

    POST /api/v1/telemetry/events

The telemetry API accepts multiple events in one request.

Batch APIs should define:

- maximum batch size
- request size limits
- validation behavior
- duplicate behavior
- processing model
- error behavior

---

## 24. Telemetry Batch API

The telemetry ingestion API supports:

    POST /api/v1/telemetry/events

The request contains an events collection.

The API validates the batch before dispatching asynchronous processing.

The platform limits batch size to protect:

- API memory
- request latency
- queue payload size
- worker processing
- database load

---

## 25. Asynchronous API Responses

Long-running operations should not unnecessarily block HTTP requests.

For asynchronous operations, use:

    202 Accepted

The response should indicate that the request was accepted for processing.

The asynchronous operation should be observable through:

- logs
- job state
- events
- operational metrics

where appropriate.

---

## 26. API Timeouts

API requests should have bounded execution time.

Avoid allowing requests to wait indefinitely for:

- database operations
- external services
- queue operations
- network calls

Long-running work should normally be moved to asynchronous processing.

---

## 27. Rate Limiting

Public or sensitive APIs should have rate limits.

Rate limiting protects against:

- accidental traffic spikes
- abusive clients
- denial-of-service patterns
- resource exhaustion

Rate limits should be appropriate to the API type.

Telemetry ingestion may require a different limit from administrative APIs.

---

## 28. Request Size Limits

APIs should enforce request-size limits.

This is particularly important for bulk telemetry ingestion.

Limits protect against:

- memory exhaustion
- oversized queue payloads
- excessive database workload
- abuse

Batch size and HTTP body size should be aligned.

---

## 29. API Security

API security should include:

- HTTPS
- authentication
- authorization
- input validation
- rate limiting where appropriate
- safe error handling
- secure headers
- request size limits
- audit logging for sensitive operations

Security controls should be applied at the correct boundary.

---

## 30. Database Protection

API consumers must not be able to directly influence unrestricted database behavior.

Avoid exposing:

- raw SQL
- arbitrary field selection without controls
- arbitrary sort expressions
- unrestricted filtering expressions

API query parameters should map to explicitly supported application behavior.

---

## 31. API Performance

API performance should be measured using:

- request latency
- throughput
- error rate
- database latency
- downstream latency

Avoid:

- N+1 queries
- unnecessary database calls
- unbounded responses
- expensive synchronous operations

Performance optimization should be evidence-driven.

---

## 32. API Compatibility

Existing API consumers should continue working after non-breaking changes.

Generally safe changes include:

- adding optional response fields
- adding optional request fields
- adding new endpoints

Potentially breaking changes include:

- removing fields
- changing field meaning
- changing field types
- changing required behavior
- removing endpoints
- changing authentication requirements

Breaking changes require explicit review.

---

## 33. API Deprecation

Deprecated APIs should follow a documented lifecycle.

Example:

    Active
      ↓
    Deprecated
      ↓
    Migration Window
      ↓
    Retired

Consumers should receive advance notice where appropriate.

The replacement API should be documented before retirement.

---

## 34. API Documentation

Every public API should document:

- endpoint
- HTTP method
- authentication
- authorization
- request parameters
- request body
- response body
- status codes
- error responses
- rate limits
- idempotency behavior
- pagination
- examples

OpenAPI can be used as the machine-readable API contract.

---

## 35. API Observability

API monitoring should capture:

- request count
- latency
- HTTP status codes
- 4xx rate
- 5xx rate
- slow endpoints
- authentication failures
- authorization failures

Logs should contain correlation identifiers.

---

## 36. API Logging

Logs should provide enough context to investigate failures.

Recommended fields include:

    request_id
    tenant_id
    endpoint
    method
    status_code
    duration_ms

For telemetry:

    event_id
    source_id
    job_id

Never log:

- passwords
- access tokens
- secret keys
- database credentials
- sensitive payload data

---

## 37. API Testing

Every API should have tests for:

### Happy Path

Valid request produces expected result.

### Validation

Invalid request is rejected.

### Authentication

Unauthenticated request is rejected.

### Authorization

Unauthorized access is rejected.

### Not Found

Missing resources return appropriate response.

### Conflict

State conflicts return appropriate response.

### Failure

Unexpected failures produce safe responses.

### Performance

Important high-volume APIs are load-tested.

---

## 38. Telemetry API Testing

Telemetry ingestion should test:

- valid event
- invalid event
- empty batch
- maximum batch size
- batch larger than maximum
- duplicate event
- asynchronous dispatch
- retry behavior
- retry exhaustion
- DLQ
- replay
- idempotency

Failure behavior is part of the API contract.

---

## 39. API Contract Ownership

Each API should have an identified owner.

The owner is responsible for:

- contract stability
- documentation
- compatibility
- monitoring
- deprecation
- consumer communication

API contracts should not change casually.

---

## 40. API Change Review

Before changing an API, review:

1. affected consumers
2. backward compatibility
3. security implications
4. performance impact
5. database impact
6. observability impact
7. deployment strategy
8. rollback strategy

Breaking changes require explicit architectural review.

---

## 41. API Design Checklist

Before releasing an endpoint, verify:

- Is the resource name correct?
- Is the HTTP method appropriate?
- Is the API version defined?
- Is input validated?
- Is authentication required?
- Is authorization enforced?
- Is tenant isolation enforced?
- Are status codes correct?
- Is the error contract defined?
- Is pagination required?
- Is rate limiting required?
- Is idempotency required?
- Is the endpoint observable?
- Is it documented?
- Is it backward-compatible?

---

## 42. API Engineering Principle

The platform follows this principle:

> APIs are long-lived contracts. They should be predictable, secure, observable, backward-compatible, and designed for evolution.

A successful API is not simply an endpoint that returns the correct response.

It is an interface that consumers can safely depend on over time.
