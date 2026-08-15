# ADR-00X: Telemetry Idempotency and Duplicate Protection

## Context

The telemetry ingestion system receives telemetry events from renewable-energy devices.

Telemetry data can arrive through batch ingestion and asynchronous processing. In a distributed system, the same telemetry event may be delivered more than once.

If duplicate events are inserted into the database, the system can produce incorrect results such as:

* Incorrect energy-generation totals
* Duplicate measurements
* Incorrect analytics
* Duplicate alerts
* Incorrect device statistics
* Increased storage consumption

Therefore, the telemetry ingestion pipeline must be **idempotent**.

An idempotent ingestion operation means that processing the same telemetry event multiple times produces the same final system state as processing it once.

---

## Why Duplicate Telemetry Occurs

Duplicate telemetry can occur for several reasons.

### 1. Network Retry

A device sends a telemetry event to the ingestion API.

The server successfully processes the event, but the response is lost because of a network failure.

The device does not know whether the request succeeded and sends the same event again.

```text
Device
  │
  │ Event A
  ▼
API
  │
  ▼
Database
  │
  │ Stored successfully
  X
Response lost
  │
  ▼
Device retries Event A
```

Without duplicate protection, Event A could be stored twice.

---

### 2. Client Retry

The telemetry client may automatically retry failed HTTP requests.

For example:

```text
Attempt 1 → Timeout
Attempt 2 → Success
```

The first request may actually have reached the server even though the client did not receive the response.

---

### 3. Queue Retry

When asynchronous processing is introduced, a worker may successfully process an event but fail before acknowledging the message.

The queue can then deliver the same message again.

```text
Queue
  │
  ▼
Worker
  │
  ├── Process successfully
  │
  └── Worker fails before acknowledgement
           │
           ▼
       Message retry
```

The worker therefore needs to safely process duplicate messages.

---

### 4. Batch Re-submission

A complete telemetry batch may be submitted again because the sender did not receive a successful response.

For example:

```text
Batch 001

Event A
Event B
Event C
```

The sender may resend the entire batch.

The ingestion system must recognize that A, B and C have already been processed.

---

### 5. Worker or Service Failure

A service can fail after storing an event but before completing the rest of its processing.

When the operation is retried, the same event may be processed again.

---

# Why `event_id` Is Used

Each telemetry event is assigned a unique `event_id`.

Example:

```json
{
    "event_id": "evt_01JXYZ123",
    "device_id": 101,
    "timestamp": "2026-08-15T10:00:00Z",
    "power_kw": 52.4,
    "energy_kwh": 148.7
}
```

The `event_id` represents the identity of the telemetry event.

The important distinction is:

```text
device_id + timestamp
```

does not necessarily guarantee uniqueness.

Two events could potentially have the same timestamp, or a device could send multiple measurements within the same timestamp resolution.

Therefore, the system uses:

```text
event_id
```

as the idempotency key.

The same event must retain the same `event_id` when it is retried.

For example:

```text
First attempt:
event_id = evt_123

Retry:
event_id = evt_123
```

The server can therefore determine that both requests represent the same logical event.

---

# Why Database Uniqueness Is Required

Application-level duplicate checking alone is not sufficient.

A naive implementation might do:

```text
Check whether event_id exists
        │
        ▼
If not found
        │
        ▼
Insert event
```

However, concurrent requests can create a race condition.

Example:

```text
Request A                  Request B
   │                          │
   │ Check event_id           │
   │ → Not found              │
   │                          │
   │                          │ Check event_id
   │                          │ → Not found
   │                          │
   ▼                          ▼
 Insert                    Insert
```

Both requests can observe that the event does not exist and both can attempt to insert it.

Therefore, the database must enforce uniqueness.

The telemetry table should have a unique constraint:

```text
event_id UNIQUE
```

Conceptually:

```text
telemetry_events
-------------------------
id
event_id       UNIQUE
device_id
timestamp
power_kw
energy_kwh
temperature
created_at
updated_at
```

The database becomes the final authority for duplicate protection.

This provides protection even when:

* multiple API requests arrive concurrently
* multiple workers process the same message
* retries happen simultaneously
* application-level checks fail

---

# How Duplicate Requests Are Handled

The ingestion system follows this process:

```text
                    Telemetry Event
                           │
                           ▼
                    Validate Payload
                           │
                           ▼
                    Validate event_id
                           │
                           ▼
                 Attempt to persist event
                           │
                 ┌─────────┴─────────┐
                 │                   │
                 ▼                   ▼
              Success             Duplicate
                 │                   │
                 ▼                   ▼
              Store              Ignore / Handle
                 │                   │
                 └─────────┬─────────┘
                           ▼
                     Final Response
```

For a new event:

```text
event_id = evt_123

→ Event does not exist
→ Insert succeeds
→ Event is processed
```

For a duplicate:

```text
event_id = evt_123

→ Event already exists
→ Database uniqueness prevents another record
→ Duplicate is handled safely
→ No second telemetry record is created
```

---

# Batch Handling

The same principle applies to batch ingestion.

Example batch:

```text
Event A
Event B
Event A
Event C
```

The expected result is:

```text
Event A → Stored
Event B → Stored
Event A → Duplicate
Event C → Stored
```

Final database state:

```text
Event A
Event B
Event C
```

There should not be two copies of Event A.

The ingestion response can also provide useful information such as:

```json
{
    "accepted": 3,
    "duplicates": 1,
    "failed": 0
}
```

This makes the batch-processing behavior observable to the caller.

---

# Failure Scenarios

## Scenario 1 — Request Timeout

```text
Client
  │
  │ Event A
  ▼
API
  │
  ▼
Database
  │
  ▼
Stored
  │
  X
Response lost
```

Client retries Event A.

Because the same `event_id` is used:

```text
Event A → Duplicate
```

No second record is created.

---

## Scenario 2 — Worker Retry

```text
Queue
  │
  ▼
Worker
  │
  ▼
Store Event A
  │
  X
Worker crashes
```

The queue retries Event A.

The second attempt uses the same `event_id`.

```text
Event A → Already exists
```

The worker does not create another record.

---

## Scenario 3 — Concurrent Requests

Two requests containing the same event arrive at almost exactly the same time.

```text
Request A ─────┐
               ├──► Database
Request B ─────┘
```

The unique database constraint guarantees that only one insertion succeeds.

The other request is handled as a duplicate.

---

## Scenario 4 — Duplicate Event in the Same Batch

```text
Batch:

A
B
A
C
```

The first A is stored.

The second A is rejected/identified as duplicate.

The batch continues processing B and C according to the batch error-handling policy.

---

## Scenario 5 — Invalid Event

An event may have an invalid payload:

```text
Missing device_id
Invalid timestamp
Invalid power value
Missing event_id
```

This should be handled as a validation failure rather than a duplicate.

```text
Validation Failure ≠ Duplicate
```

These cases should remain distinguishable for monitoring and debugging.

---

# Trade-offs

## Advantages

### 1. Data Integrity

Duplicate telemetry does not corrupt energy calculations or analytics.

### 2. Safe Retries

Clients, queues and workers can retry operations without creating duplicate records.

### 3. Distributed-System Safety

The design works better when multiple workers or service instances process telemetry concurrently.

### 4. Simple Idempotency Model

Using a unique `event_id` provides a straightforward mechanism for identifying repeated events.

### 5. Database-Level Protection

The uniqueness constraint provides a final safety boundary independent of application logic.

---

## Disadvantages

### 1. Additional Storage

Every event needs an `event_id`, increasing the size of each record slightly.

### 2. Unique Index Overhead

The database needs a unique index on `event_id`.

This consumes storage and adds some write overhead.

### 3. Client Responsibility

The telemetry producer must generate and preserve the same `event_id` when retrying an event.

If every retry generates a new ID, the server cannot recognize it as the same logical event.

### 4. Duplicate Handling Complexity

Batch processing and asynchronous workers need explicit duplicate-handling logic.

### 5. Event Retention Considerations

If historical event IDs are removed too aggressively, an old event that is resent later could potentially be treated as a new event.

Therefore, the retention policy for telemetry and idempotency information must be considered as the system scales.

---

# Decision

The telemetry platform will use **event-level idempotency** based on a unique `event_id`.

The following rules apply:

1. Every telemetry event must contain an `event_id`.
2. The same logical event must retain the same `event_id` across retries.
3. `event_id` must have a database-level unique constraint.
4. Duplicate events must not create additional telemetry records.
5. Application logic should handle duplicate insertion attempts gracefully.
6. Batch ingestion must distinguish between accepted, duplicate and failed events.
7. Asynchronous workers must be designed to safely handle message redelivery.

This design provides a reliable foundation for the next stages of the telemetry processing pipeline.

---

# Result

The telemetry ingestion system now has an important distributed-system guarantee:

```text
Same Event
   │
   ├── First delivery  ──► Process
   │
   ├── Retry            ──► Ignore duplicate
   │
   ├── Queue redelivery ──► Ignore duplicate
   │
   └── Batch resend     ──► Ignore duplicate
```

Therefore:

> **Multiple deliveries of the same telemetry event result in one logical telemetry record.**
