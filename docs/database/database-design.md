# Database Design Overview

## Database responsibilities

The platform separates application data from telemetry data.

| Store | Responsibility |
|---|---|
| MySQL | Core application/domain data |
| PostgreSQL / Supabase | Telemetry data |
| Redis | Queue/cache support |

## Domain relationship

```text
User
 |
 +--> Plant
       |
       +--> Asset
              |
              +--> Device
                     |
                     +--> Telemetry
```

The exact physical schema is implemented through Laravel migrations and should be treated as the authoritative database definition.

## Application database

The primary Laravel database contains the platform's operational/domain records, including entities such as:

- users
- plants
- assets
- devices
- alerts
- queue/application support tables

Foreign-key relationships enforce the domain hierarchy where implemented.

## Telemetry database

Telemetry is stored through the dedicated `pgsql_telemetry` connection.

The separation allows high-volume telemetry persistence to evolve independently from transactional application data.

Telemetry records are associated with device identity and event time. The processing path also relies on idempotency/uniqueness to protect against duplicate delivery.

## Queue data

The current asynchronous telemetry implementation uses a database-backed queue.

Queue data should be considered operational infrastructure rather than telemetry business data.

## Schema changes

Use Laravel migrations for application schema changes.

Typical workflow:

```bash
php artisan migrate:status
php artisan migrate
```

Before production changes:

1. Review the migration.
2. Identify affected tables/indexes.
3. Consider backward compatibility with the deployed application version.
4. Back up data where appropriate.
5. Apply the migration.
6. Verify application readiness and critical flows.

## High-volume telemetry considerations

Telemetry is the high-volume portion of the system.

The architecture keeps ingestion asynchronous and separates telemetry persistence from the main application database.

Future scaling strategies can include:

- partitioning
- time-based retention
- read replicas
- archival
- dedicated time-series storage
- workload-specific indexes

These are evolution paths, not assumptions about the current implementation.

## Source of truth

For exact columns, indexes, constraints and migration ordering, use:

`backend/database/migrations/`

The requirements document is a design reference, not a replacement for executable migrations.
