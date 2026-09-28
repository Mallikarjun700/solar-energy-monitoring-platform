# Database Requirements

## Purpose

Define the database responsibilities and reliability requirements for the Solar Energy Monitoring & Asset Management Platform.

## Data stores

### Primary application database

The Laravel application uses MySQL for transactional/domain data.

Core domain areas include:

- users and authorization
- plants
- assets
- devices
- alerts
- queue/application support data

### Telemetry database

Telemetry uses a separate PostgreSQL connection named `pgsql_telemetry`.

The current telemetry PostgreSQL deployment is hosted by Supabase.

The separation keeps high-volume telemetry workloads independent from the primary application database.

### Redis

Redis supports application cache/queue workloads according to the active Laravel configuration.

## Relationship requirements

The core resource hierarchy is:

```text
Plant
  |
  +-- Asset
       |
       +-- Device
            |
            +-- Telemetry
```

Requirements:

- an asset must belong to a plant
- a device must belong to an asset
- telemetry must identify its source device
- resource relationships must be validated before persistence

## Telemetry requirements

Telemetry storage must support:

- device identity
- event timestamp
- operational measurements defined by the telemetry model
- efficient device/time-range queries
- duplicate-event protection
- asynchronous ingestion

The processing layer must tolerate duplicate delivery.

## Performance and scalability

The architecture should allow telemetry storage to scale independently from transactional application data.

Future scaling options may include:

- partitioning
- time-based retention
- archival
- specialized time-series storage
- read replicas
- workload-specific indexes

These are future evolution options and should not be treated as current implementation unless reflected in migrations/infrastructure.

## Availability and recovery

Database-related failures must be observable and recoverable.

The telemetry pipeline must support:

`Failure → Retry → DLQ → Replay`

Backups and restoration procedures are documented in [Backup & Restore](../architecture/operations/backup-restore.md).

## Schema source of truth

The exact physical schema is defined by:

`backend/database/migrations/`

The [Database Design Overview](database-design.md) describes the architecture and relationships without duplicating migration definitions.

## Change requirements

Database changes should:

1. use Laravel migrations where applicable
2. be reviewed for backward compatibility
3. avoid destructive changes during an incompatible application rollout
4. be covered by appropriate tests
5. be included in the release plan
6. be verified after deployment
