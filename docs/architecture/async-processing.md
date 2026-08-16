# Asynchronous Telemetry Processing

## Problem

Telemetry ingestion can receive a large number of events in a short period.

Processing every event synchronously inside the HTTP request creates several
problems:

- Increased API response time
- Limited ingestion throughput
- Processing failures affect API requests
- Database spikes can affect ingestion
- Long-running processing can cause request timeouts
- Horizontal scaling becomes harder

## Current Flow

Client
  ↓
API
  ↓
Telemetry Processing
  ↓
Database

The API request waits for telemetry processing to complete.

## Proposed Flow

Client
  ↓
Ingestion API
  ↓
Message Queue
  ↓
Background Worker
  ↓
Telemetry Processing
  ↓
Database

The API acknowledges the event after successfully placing it into the queue.

## Why Asynchronous Processing?

Asynchronous processing provides:

- Faster API responses
- Higher ingestion throughput
- Independent worker scaling
- Better failure isolation
- Queue-based buffering
- Backpressure handling
- Improved system resilience

## Trade-offs

### Advantages

- Decouples ingestion from processing
- Workers can scale independently
- Queue absorbs traffic spikes
- Long-running processing does not block API requests

### Disadvantages

- Adds infrastructure complexity
- Introduces eventual consistency
- Requires queue monitoring
- Requires worker management
- Requires duplicate-message protection

## Important Design Principle

The queue provides delivery, but does not guarantee that an event will
never be delivered more than once.

Therefore telemetry processing must remain idempotent.

The existing event_id-based idempotency mechanism is retained.