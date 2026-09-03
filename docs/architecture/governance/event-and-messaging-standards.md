# Event and Messaging Standards

## 1. Purpose

This document defines event and messaging standards for the Solar Energy Monitoring Platform.

The platform uses asynchronous processing for workloads such as telemetry processing.

The objective is to ensure messaging remains:

- reliable
- scalable
- observable
- idempotent
- versioned
- secure
- maintainable

---

## 2. Messaging Architecture

The telemetry processing flow is:

    Telemetry Client
          ↓
    REST API
          ↓
    Validation
          ↓
    Queue
          ↓
    Queue Worker
          ↓
    Telemetry Database

Failures follow:

    Processing Failure
          ↓
       Retry
          ↓
    Retry Exhausted
          ↓
         DLQ
          ↓
       Replay

---

## 3. Messaging Principles

The platform follows these principles:

1. Assume messages can be delivered more than once.
2. Consumers must be idempotent where duplicate processing is possible.
3. Messages must have stable identities.
4. Event schemas must be versioned.
5. Failures must be observable.
6. Retry only failures that may recover.
7. Permanently failed messages must not remain in the normal queue indefinitely.
8. Replay must be controlled and safe.
9. Producers and consumers must evolve independently.
10. Message contracts must be treated as APIs.

---

## 4. Producer Responsibilities

A message producer is responsible for:

- creating valid messages
- assigning event identity
- setting timestamps
- defining schema version
- including required metadata
- validating payloads
- avoiding sensitive information
- publishing messages reliably

Producers must not assume that consumers process messages exactly once.

---

## 5. Consumer Responsibilities

Consumers are responsible for:

- validating message structure
- handling duplicates safely
- processing within configured timeouts
- retrying appropriate failures
- logging failures
- preserving correlation information
- handling poison messages
- sending exhausted failures to the DLQ

Consumers should not depend on message delivery being exactly once.

---

## 6. Event Identity

Every event must have a stable unique identifier.

For telemetry:

    event_id

The event ID identifies the logical event rather than a particular delivery attempt.

Example:

    Event
      event_id = abc-123

If the same event is delivered three times:

    Delivery 1 → abc-123
    Delivery 2 → abc-123
    Delivery 3 → abc-123

all deliveries represent the same logical event.

---

## 7. Event IDs and Idempotency

Event identity is the foundation of idempotent processing.

The processing model is:

    At-Least-Once Delivery
             +
    Stable Event ID
             +
    Database Uniqueness
             =
    Safe Duplicate Handling

The database remains the final integrity boundary.

---

## 8. Message Envelope

Where appropriate, messages should use a consistent envelope.

Example:

    {
      "event_id": "uuid",
      "event_type": "telemetry.power",
      "schema_version": 1,
      "tenant_id": "uuid",
      "source_id": "uuid",
      "event_timestamp": "timestamp",
      "correlation_id": "uuid",
      "payload": {}
    }

The envelope separates message metadata from business payload.

---

## 9. Required Metadata

Events should include appropriate metadata such as:

- event_id
- event_type
- schema_version
- tenant_id
- source_id
- event_timestamp

Additional metadata may include:

- correlation_id
- producer
- created_at

Only required metadata should be added.

---

## 10. Event Type Naming

Event types should describe business meaning.

Prefer:

    telemetry.power
    telemetry.temperature
    asset.created
    device.status_changed

Avoid:

    processData
    doSomething
    event1

Event names should remain stable once consumers depend on them.

---

## 11. Schema Versioning

Every important event contract should have a schema version.

Example:

    schema_version: 1

When a compatible schema evolves:

    version 1
       ↓
    version 2

Consumers should be able to determine which schema they received.

---

## 12. Backward-Compatible Event Changes

Prefer additive changes.

Examples:

- adding optional fields
- adding metadata
- adding optional payload properties

Avoid silently changing:

- field meaning
- field type
- required semantics

Breaking changes should use a new schema version or explicitly coordinated migration.

---

## 13. Event Contract Ownership

Every important event contract should have an owner.

The owner is responsible for:

- schema definition
- compatibility
- documentation
- consumer communication
- deprecation
- version lifecycle

Events should not become undocumented internal contracts.

---

## 14. Delivery Semantics

The telemetry pipeline assumes:

> At-least-once delivery.

This means a message may be delivered:

- once
- more than once
- after a temporary failure

Consumers must therefore be designed for duplicate delivery.

Exactly-once processing should not be assumed unless explicitly guaranteed by the infrastructure and verified for the specific workload.

---

## 15. Duplicate Message Handling

Duplicate messages should be handled using:

- stable event IDs
- database uniqueness constraints
- atomic operations
- idempotent processing

Avoid relying only on:

    "Check whether event exists, then insert."

Concurrent consumers can race.

Database constraints provide stronger protection.

---

## 16. Message Ordering

Consumers should not assume global message ordering unless the architecture explicitly guarantees it.

Telemetry events may arrive:

- out of order
- late
- duplicated

If ordering is required for a specific business operation, it must be explicitly designed.

---

## 17. Event Timestamp

Telemetry events should preserve the timestamp generated by the source.

For example:

    event_timestamp

This should be distinguished from:

    received_at

and:

    processed_at

These timestamps represent different stages of the event lifecycle.

---

## 18. Late Events

Consumers should define how late-arriving events are handled.

Possible strategies include:

- accept the event
- reject events older than a defined threshold
- process using event time
- store for later reconciliation

The correct strategy depends on business requirements.

---

## 19. Message Size

Messages should remain reasonably small.

Avoid placing unnecessarily large data inside queue messages.

For large payloads:

    Message
       ↓
    Reference to external object
       ↓
    Object Storage

Large messages increase:

- network overhead
- memory usage
- processing time
- retry cost

---

## 20. Batch Messaging

Batch processing may be used for high-volume telemetry.

Benefits:

- fewer queue operations
- lower overhead
- better database throughput

Batch size must remain bounded.

Large batches increase:

- memory usage
- processing duration
- failure scope
- retry cost

---

## 21. Batch Failure Semantics

A batch failure should not automatically imply that every event is permanently invalid.

The system should identify:

- batch-level failures
- event-level failures

Where practical, failed events should remain individually identifiable.

This supports targeted retry and DLQ handling.

---

## 22. Retryable Failures

Retries are appropriate for transient failures such as:

- temporary database connectivity
- transient network errors
- temporary dependency unavailability
- infrastructure interruptions

Retry policies should be bounded.

---

## 23. Non-Retryable Failures

Do not repeatedly retry permanent failures such as:

- invalid event schema
- invalid required fields
- unsupported event type
- permanent business-rule violation
- unrecoverable data corruption

These failures should move toward the DLQ or another controlled failure path.

---

## 24. Retry Count

Consumers must define a maximum retry count.

Example:

    Attempt 1
       ↓
    Attempt 2
       ↓
    Attempt 3
       ↓
    DLQ

The exact retry count should be based on failure characteristics.

Unlimited retries are not acceptable.

---

## 25. Retry Backoff

Retries should normally use backoff.

Example:

    Attempt 1 → immediate
    Attempt 2 → short delay
    Attempt 3 → longer delay
    Attempt 4 → longer delay

Backoff reduces pressure on a failing dependency.

Exponential backoff with controlled limits is preferred where appropriate.

---

## 26. Retry Jitter

Where many workers may retry simultaneously, jitter can reduce synchronized retry bursts.

Example:

    Base Delay
       +
    Random Jitter
       ↓
    Retry

This helps prevent a thundering-herd effect.

---

## 27. Retry Idempotency

Retries must be compatible with idempotent processing.

Example:

    First attempt
        ↓
    Database operation succeeds
        ↓
    Worker times out before acknowledging
        ↓
    Message delivered again
        ↓
    Duplicate event detected
        ↓
    No duplicate record created

This is why idempotency and retry design must be considered together.

---

## 28. Poison Messages

A poison message is a message that repeatedly fails processing.

Examples:

- malformed payload
- unsupported schema
- permanently invalid data
- deterministic processing exception

Poison messages must not continuously consume worker capacity.

They should eventually enter the DLQ or equivalent failure mechanism.

---

## 29. Dead Letter Queue

The DLQ provides isolation for messages that cannot be successfully processed.

A message may enter the DLQ because:

- retry attempts are exhausted
- the failure is non-retryable
- processing repeatedly fails
- the message is identified as invalid

The DLQ preserves the event for investigation and possible replay.

---

## 30. DLQ Data

DLQ records should preserve enough information for investigation.

Important fields include:

- event_id
- original payload
- error type
- failure reason
- attempt count
- first failure timestamp
- last failure timestamp
- status

This information should allow operators to understand why processing failed.

---

## 31. DLQ Isolation

DLQ events should not continuously re-enter the normal processing path.

This prevents:

- infinite retry loops
- queue starvation
- worker exhaustion
- repeated processing of known failures

DLQ processing should be deliberate.

---

## 32. DLQ Replay

Replay should follow:

    Identify failure
        ↓
    Fix root cause
        ↓
    Select eligible events
        ↓
    Replay
        ↓
    Idempotent processing
        ↓
    Success / DLQ again

Do not replay events blindly.

---

## 33. Replay Safety

Replay must preserve:

- original event identity
- original business payload
- appropriate metadata
- auditability

A replay should not create a new logical event ID unless the business semantics explicitly require a new event.

---

## 34. Replay Controls

Replay operations should support controlled execution.

Possible controls include:

- filtering by event ID
- filtering by status
- limiting replay count
- replaying in batches
- monitoring replay results
- stopping replay if failure rate increases

Large-scale replay should be treated as an operational activity.

---

## 35. Message Retention

Message retention should be aligned with:

- business requirements
- recovery requirements
- replay requirements
- operational investigation

Retention should not be indefinite without justification.

---

## 36. Message Security

Messages should contain only information required for processing.

Avoid placing:

- passwords
- access tokens
- secret keys
- unnecessary personal data

inside messages.

Sensitive data should be protected through appropriate encryption and access controls.

---

## 37. Message Encryption

Data should be protected:

- in transit
- at rest

Queue and supporting infrastructure should use encryption capabilities appropriate to the environment.

---

## 38. Access Control

Only authorized services should be able to:

- publish messages
- consume messages
- inspect messages
- replay messages
- modify DLQ state

Replay and DLQ operations should have stronger operational controls than normal consumption.

---

## 39. Correlation

Messages should preserve correlation information where useful.

Important identifiers include:

    request_id
    correlation_id
    event_id
    tenant_id
    source_id
    job_id

Correlation allows an operator to follow:

    API Request
        ↓
    Event
        ↓
    Queue Job
        ↓
    Database Operation
        ↓
    Failure / Success

---

## 40. Messaging Observability

Monitor:

- queue depth
- oldest message age
- processing latency
- processing throughput
- retry count
- failure rate
- DLQ count
- replay success rate
- worker utilization

Metrics should help identify both current failures and gradual degradation.

---

## 41. Messaging Logs

Important message-processing logs should contain:

- event_id
- event_type
- schema_version
- job_id
- tenant_id
- source_id
- attempt_count
- processing result
- error category

Sensitive payload contents should not be logged unnecessarily.

---

## 42. Consumer Timeout

Message processing must have a bounded timeout.

The timeout should account for:

- expected processing time
- database operations
- network operations

The timeout must be coordinated with queue visibility or acknowledgement behavior to avoid duplicate concurrent processing caused by premature message re-delivery.

---

## 43. Acknowledgement

A message should be acknowledged only after the required processing is successfully completed.

Conceptually:

    Receive Message
         ↓
    Process
         ↓
    Persist Result
         ↓
    Success
         ↓
    Acknowledge

If processing fails before successful completion, the message should remain eligible for retry according to queue semantics.

---

## 44. Consumer Idempotency

Consumers should be safe when receiving the same message multiple times.

Idempotency may use:

- event IDs
- unique constraints
- idempotency tables
- state transitions
- atomic operations

The mechanism should match the business operation.

---

## 45. Message State

Where message processing state is persisted, use explicit states.

Example:

    pending
    processing
    processed
    failed
    dead_lettered
    replayed

State transitions should be documented.

---

## 46. Event State vs Message State

An important distinction is:

### Event

The logical business event.

### Message

A delivery of that event through messaging infrastructure.

One event may result in multiple message deliveries.

The event identity should therefore remain stable across retries.

---

## 47. Failure Isolation

One bad event should not unnecessarily stop unrelated events.

Workers should isolate failures where possible.

For example:

    Event A → Success
    Event B → Failure
    Event C → Success

Event B should enter its failure path without preventing successful processing of A and C.

---

## 48. Backpressure

The system must recognize when consumers cannot keep up with producers.

Symptoms include:

- growing queue depth
- increasing message age
- worker saturation
- increasing processing latency

Possible responses:

- scale workers
- reduce producer rate where possible
- optimize processing
- increase batch efficiency
- investigate downstream bottlenecks

---

## 49. Queue Capacity Planning

Queue capacity must consider:

    Incoming Rate
          vs
    Processing Rate

If:

    Incoming Rate > Processing Rate

backlog increases.

Worker scaling should restore:

    Processing Rate > Incoming Rate

long enough to drain the backlog.

---

## 50. Event Evolution

Events should evolve without requiring all consumers to update simultaneously.

Preferred approach:

    Producer supports old + new
             ↓
    Consumers migrate
             ↓
    New schema becomes standard
             ↓
    Old schema deprecated
             ↓
    Old schema retired

This reduces coordinated deployment requirements.

---

## 51. Breaking Event Changes

Breaking changes include:

- removing required fields
- changing field types
- changing field meaning
- changing event semantics

Breaking changes require:

- explicit versioning
- consumer analysis
- migration plan
- deployment coordination
- rollback strategy

---

## 52. Event Testing

Events should be tested for:

- schema validity
- required metadata
- serialization
- deserialization
- backward compatibility
- duplicate delivery
- retry behavior
- DLQ behavior
- replay behavior

Failure paths must be tested.

---

## 53. Contract Testing

Important event producers and consumers should use contract tests where practical.

Contract tests verify that:

    Producer Contract
          ↕
    Consumer Expectations

remain compatible.

This reduces accidental event-schema breakage.

---

## 54. Messaging Performance Testing

Test:

- messages per second
- batch throughput
- worker throughput
- processing latency
- retry behavior
- queue backlog
- backlog recovery

Performance testing should include realistic payload sizes.

---

## 55. Operational Runbook

Messaging incidents should provide operators with procedures for:

- queue backlog
- worker failures
- retry spikes
- DLQ growth
- poison messages
- replay
- dependency failures

Runbooks should identify:

- symptoms
- investigation steps
- mitigation
- recovery
- validation

---

## 56. Messaging Governance Checklist

Before introducing a new event or queue, verify:

- What business problem does it solve?
- Who produces it?
- Who consumes it?
- What is the event identity?
- What is the schema?
- What is the schema version?
- What delivery semantics are expected?
- Is idempotency required?
- Can messages arrive out of order?
- What failures are retryable?
- What is the retry limit?
- What happens after retry exhaustion?
- Is a DLQ required?
- How is replay performed?
- What identifiers enable correlation?
- What metrics and alerts are required?
- How will the contract evolve?

---

## 57. Engineering Principle

The platform follows this principle:

> Assume messages can be duplicated, delayed, reordered, or failed; design explicit identity, idempotency, retry, failure isolation, and recovery mechanisms accordingly.

Messaging is not simply a transport mechanism.

It is a reliability boundary between independently executing components.
