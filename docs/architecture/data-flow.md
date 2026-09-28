# End-to-End Data Flow

## System overview

```text
                    +----------------------+
                    | Angular Frontend     |
                    +----------+-----------+
                               |
                               v
                    +----------------------+
                    | Laravel API          |
                    | Sanctum + abilities  |
                    +----+------------+----+
                         |            |
                  App data|            |Telemetry
                         v            v
                 +-------+--+    +----+------------------+
                 | MySQL    |    | Queue / Async Job     |
                 +----------+    +-----------+------------+
                                             |
                                             v
                                   +---------+---------+
                                   | Telemetry Worker  |
                                   | TelemetryService  |
                                   +---------+---------+
                                             |
                                             v
                                   +--------------------+
                                   | Supabase PostgreSQL|
                                   +--------------------+

Failure path:
Telemetry Worker → Retry/Backoff → DLQ → Inspect → Replay → Worker
```

## Request path

The Angular application communicates with Laravel through REST APIs.

Authentication is handled by Sanctum. Fine-grained endpoint access is enforced through abilities and role checks.

## Application data path

Plant, asset, device, user and related application data use the primary application database.

The hierarchy is:

`Plant → Asset → Device`

## Telemetry path

Telemetry is intentionally separated from the synchronous application request path.

`Telemetry client → API → queue → worker → telemetry PostgreSQL`

This prevents the API request from waiting for the complete persistence pipeline.

## Reliability path

A processing failure does not immediately become permanent data loss.

```text
Failure
  |
  v
Retry
  |
  +--> success
  |
  +--> retry exhausted
          |
          v
         DLQ
          |
          v
        Replay
          |
          v
       Process
```

## Duplicate protection

An event can be delivered more than once.

The processing layer uses idempotency/database uniqueness so duplicate delivery does not create duplicate logical telemetry.

## Deployment data flow

### Render

`GitHub Actions → Render backend/frontend services`

### AWS

`GitHub Actions → ECR → ECS/Fargate → ALB → application`

The AWS deployment path also includes worker and scheduler services.

## Design principle

The architecture separates:

- user-facing request handling
- durable asynchronous processing
- telemetry persistence
- failure recovery
- deployment verification

This separation makes failure modes explicit and easier to operate.
