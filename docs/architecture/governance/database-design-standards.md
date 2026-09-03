# Database Design Standards

## 1. Purpose

This document defines database design standards for the Solar Energy Monitoring Platform.

The platform uses separate database workloads for:

- application data
- telemetry data

The objective is to maintain:

- data integrity
- predictable performance
- scalability
- maintainability
- efficient queries
- safe schema evolution

---

## 2. Database Architecture

The platform separates application and telemetry workloads.

Architecture:

    Application Services
           |
           +---- MySQL
           |      |
           |      +-- Users
           |      +-- Assets
           |      +-- Devices
           |      +-- Application Data
           |
           +---- PostgreSQL
                  |
                  +-- Telemetry Events
                  +-- High-volume Telemetry Data

This separation prevents high-volume telemetry writes from unnecessarily competing with core application transactions.

---

## 3. Database Ownership

Each database should have a clearly defined responsibility.

### MySQL

Primary application database.

Responsible for:

- application entities
- users
- assets
- devices
- business relationships
- application configuration

### PostgreSQL

Telemetry workload.

Responsible for:

- telemetry events
- high-volume telemetry data
- telemetry processing state where appropriate

Avoid storing the same business data in multiple databases without a documented reason.

---

## 4. Table Naming

Table names should:

- use snake_case
- use plural nouns
- represent the resource

Examples:

    users
    assets
    devices
    telemetry_events
    dead_letter_events

Avoid:

    User
    AssetTable
    tbl_devices

---

## 5. Column Naming

Column names should use snake_case.

Examples:

    event_id
    tenant_id
    source_id
    event_type
    event_timestamp
    created_at
    updated_at

Avoid inconsistent naming such as:

    eventId
    EventID
    eventDateTime

---

## 6. Primary Keys

Every persistent table should have a clearly defined primary key.

Primary keys should:

- be stable
- be unique
- never change
- not contain business meaning

The appropriate key type depends on the workload.

---

## 7. UUID Usage

UUIDs are appropriate for distributed identifiers such as:

- event_id
- tenant_id
- source_id
- external resource identifiers

UUIDs reduce collision risk across distributed producers.

For high-volume tables, UUID storage and indexing strategy should be evaluated for performance.

---

## 8. Business Identifiers

Business identifiers should not automatically be used as primary keys.

For example:

    event_id

is a business/event identity.

The database may additionally maintain an internal primary key where appropriate.

Business identifiers that must be unique should use explicit unique constraints.

---

## 9. Foreign Keys

Foreign keys should be used when database-level referential integrity is required.

Examples:

    asset → site
    device → asset

Foreign keys should be evaluated carefully for high-volume telemetry tables where ingestion throughput and cross-database boundaries may make application-level relationships more appropriate.

---

## 10. Referential Integrity

Data relationships should have explicit ownership.

Before removing a parent record, determine what should happen to dependent records.

Possible strategies include:

- restrict deletion
- cascade deletion
- soft deletion
- archive dependent records

The choice should be based on business semantics.

---

## 11. Unique Constraints

Uniqueness requirements should be enforced at the database level.

Example:

    telemetry_events.event_id UNIQUE

Application-level duplicate checks alone are insufficient under concurrent requests.

Database constraints provide the final integrity boundary.

---

## 12. Idempotency and Database Constraints

Telemetry processing follows:

    At-Least-Once Delivery
            +
    Idempotent Processing

The database uniqueness constraint protects against concurrent duplicate processing.

Example:

    event_id
       ↓
    UNIQUE constraint
       ↓
    Duplicate prevented

This remains effective even when multiple workers process the same event.

---

## 13. Indexing Principles

Indexes should support actual query patterns.

Common indexed fields include:

- primary keys
- foreign keys
- unique identifiers
- frequently filtered fields
- frequently sorted fields

Do not add indexes simply because a column exists.

Every index has a cost.

---

## 14. Index Trade-Off

Indexes improve reads but increase:

- storage
- write cost
- update cost
- maintenance overhead

For high-volume telemetry ingestion, excessive indexing can significantly reduce write throughput.

Therefore:

> Index only fields required by important query patterns.

---

## 15. Composite Indexes

Composite indexes should follow actual query patterns.

Example:

    WHERE event_id = ?
      AND status = ?

may benefit from:

    (event_id, status)

Column order should be selected based on:

- filtering
- selectivity
- sorting
- query frequency

---

## 16. Index Review

Before adding an index:

1. identify the query
2. inspect query frequency
3. inspect query execution plan
4. determine whether an existing index is sufficient
5. estimate write overhead
6. add the index
7. measure performance improvement

Indexes should be evidence-driven.

---

## 17. Query Standards

Queries should avoid:

- SELECT *
- unbounded result sets
- unnecessary joins
- N+1 queries
- queries inside large loops
- repeated identical queries

Prefer:

- explicit columns
- eager loading
- pagination
- batch operations
- appropriate indexes

---

## 18. Pagination

Large collections must use pagination.

Avoid loading millions of records into application memory.

For large datasets, consider:

- cursor pagination
- keyset pagination
- bounded batch processing

The appropriate method depends on the query pattern.

---

## 19. N+1 Query Prevention

Application code must avoid N+1 database queries.

Example problem:

    Load 100 assets
       ↓
    Query devices for each asset
       ↓
    101 queries

Prefer:

    Load assets
       ↓
    Eager load required relationships
       ↓
    Small number of queries

Query count should be validated in important workflows.

---

## 20. Normalization

Application data should generally follow normalized relational design.

Normalization reduces:

- duplicated data
- update anomalies
- inconsistent state

Core transactional data should remain normalized unless there is a measured reason to denormalize.

---

## 21. Denormalization

Denormalization may be used when:

- read performance requires it
- reporting workloads justify it
- aggregation is expensive
- data duplication is controlled

Denormalization introduces consistency complexity.

Every denormalized field should have a documented source of truth.

---

## 22. JSON Data

JSON columns may be used for flexible data where schema variability is expected.

Telemetry may contain:

    attributes
    payload

JSON is appropriate when fields vary between event types.

However, frequently queried fields should not be hidden inside JSON without considering indexing and query performance.

---

## 23. JSON Design Rules

For JSON data:

- keep structure documented
- validate incoming data
- avoid unnecessary nesting
- avoid storing large redundant objects
- define schema expectations
- index frequently queried JSON fields when justified

JSON should provide flexibility, not replace relational modeling for stable core entities.

---

## 24. Timestamp Standards

Persistent timestamps should use consistent names:

    created_at
    updated_at

Event-related timestamps should use explicit names:

    event_timestamp
    received_at
    first_failed_at
    last_failed_at

This prevents ambiguity between business time and processing time.

---

## 25. Event Time vs Processing Time

Telemetry systems should distinguish:

### Event Timestamp

When the device/event actually occurred.

Example:

    event_timestamp

### Received Timestamp

When the platform received the event.

Example:

    received_at

### Processing Time

When the event was processed.

These timestamps answer different operational questions and should not be conflated.

---

## 26. Timezone Standards

Backend timestamps should use a consistent timezone strategy.

UTC should be preferred for distributed backend persistence.

User-facing applications may convert timestamps into the user's local timezone.

Never rely implicitly on the database server's local timezone.

---

## 27. Transactions

Transactions should be used when multiple database operations must succeed or fail together.

Example:

    BEGIN
       ↓
    Update Asset
       ↓
    Create Audit Record
       ↓
    COMMIT

If a critical operation fails:

    ROLLBACK

Transactions should remain as short as practical.

---

## 28. Transaction Boundaries

Transactions should normally remain inside the database/application boundary.

Avoid holding a database transaction open while waiting for:

- HTTP requests
- external APIs
- queue processing
- long computations

Long transactions increase:

- lock contention
- resource usage
- failure impact

---

## 29. Concurrency

Database operations must account for concurrent workers and requests.

Examples:

- duplicate telemetry events
- simultaneous asset updates
- concurrent queue workers
- DLQ replay

Use:

- unique constraints
- transactions
- atomic updates
- appropriate locking

Do not assume requests execute sequentially.

---

## 30. Race Conditions

Application-level checks can race.

Unsafe pattern:

    Check if event exists
        ↓
    If not, insert event

Two workers may both observe:

    event does not exist

and both attempt insertion.

Safer pattern:

    Database UNIQUE constraint
          +
    Insert / conflict-safe operation

The database becomes the final concurrency boundary.

---

## 31. Bulk Inserts

High-volume telemetry should use batch operations where practical.

Benefits include:

- fewer database round trips
- higher throughput
- lower connection overhead

Batch size should be bounded to prevent:

- excessive memory usage
- oversized transactions
- long lock duration
- large failure impact

---

## 32. Database Connection Management

Application services should use bounded database connections.

Monitor:

- active connections
- idle connections
- connection errors
- connection saturation

Connection limits must account for:

    ECS task count
    ×
    connections per task

Scaling ECS tasks without considering database connection capacity can overload the database.

---

## 33. Migration Standards

Every schema change should be represented as a version-controlled migration.

Migrations should be:

- deterministic
- reviewable
- repeatable
- tested
- backward-compatible where possible

Manual production schema changes should be minimized.

---

## 34. Migration Naming

Migration names should clearly describe the change.

Examples:

    create_telemetry_events_table
    create_dead_letter_events_table
    add_status_to_assets

Avoid vague names such as:

    update_table
    fix_db
    changes

---

## 35. Safe Production Migrations

Production migrations should follow:

    Review
      ↓
    Test
      ↓
    Estimate impact
      ↓
    Deploy compatible schema
      ↓
    Deploy application
      ↓
    Validate
      ↓
    Remove obsolete schema later

Prefer expand-and-contract migrations.

---

## 36. Destructive Migrations

Destructive changes require additional review.

Examples:

- dropping columns
- dropping tables
- changing column types
- deleting large datasets
- removing indexes

Never perform destructive schema changes without understanding:

- affected consumers
- rollback implications
- data loss
- migration duration

---

## 37. Soft Deletes

Soft deletes should only be used when the business requires recoverability or historical visibility.

Soft deletes can increase:

- table size
- query complexity
- index size

Do not use soft deletes automatically for every table.

---

## 38. Audit Data

Important business changes may require audit information.

Examples:

- asset ownership changes
- permission changes
- administrative actions
- configuration changes

Audit data should preserve:

- actor
- action
- timestamp
- affected resource
- relevant context

Audit requirements should be defined by the business and security model.

---

## 39. Telemetry Storage

Telemetry storage should be optimized for high write volume.

Important considerations include:

- event ingestion rate
- batch size
- indexing
- storage growth
- query patterns
- retention
- archival

Telemetry tables should avoid unnecessary indexes that reduce ingestion throughput.

---

## 40. Telemetry Retention

Telemetry retention should be explicitly defined.

Storage growth depends on:

    Devices
      ×
    Events per device
      ×
    Average event size
      ×
    Retention period

Long-term historical data may require:

- archival
- aggregation
- tiered storage
- deletion according to policy

---

## 41. Dead Letter Data

DLQ records should preserve enough information for investigation and replay.

Relevant information includes:

- event_id
- original payload
- error type
- failure reason
- attempt count
- first failure timestamp
- last failure timestamp
- status

DLQ data should be retained according to operational requirements.

---

## 42. Data Integrity

Database constraints should enforce important invariants.

Examples:

- unique event IDs
- required fields
- valid relationships
- valid state values

Application validation improves user experience.

Database constraints provide the final integrity boundary.

---

## 43. Database Security

Databases should:

- remain private
- use encrypted connections where required
- use managed credentials
- restrict network access
- use least-privilege accounts
- avoid shared credentials
- avoid exposing database ports publicly

Application credentials should not be committed to source control.

---

## 44. Database Credentials

Database credentials should be managed through secure secret-management infrastructure.

Applications should receive credentials through:

- managed secrets
- secure runtime configuration

Credentials should never be logged.

Rotation procedures should be documented.

---

## 45. Backup Considerations

Production databases should have:

- automated backups
- defined retention
- recovery procedures
- restore testing

Backup strategy must be aligned with:

- RPO
- RTO
- business requirements

A backup that cannot be restored is not a reliable recovery mechanism.

---

## 46. Database Monitoring

Monitor:

### Performance

- CPU
- memory
- IOPS
- latency

### Capacity

- storage
- connections
- table growth
- index growth

### Reliability

- availability
- connection failures
- replication health where applicable

### Query Health

- slow queries
- lock contention
- failed queries

---

## 47. Database Change Review

Every significant database change should consider:

1. performance
2. storage impact
3. locking
4. migration duration
5. backward compatibility
6. rollback strategy
7. backup/recovery implications

Database changes are architectural changes when they affect system behavior or scalability.

---

## 48. Database Testing

Database changes should be tested for:

- migration success
- migration rollback where applicable
- constraints
- indexes
- relationships
- query performance
- concurrent access
- realistic data volumes

High-volume telemetry changes should be tested with production-like workloads.

---

## 49. Database Performance Review

When a query becomes slow:

1. identify the query
2. inspect execution plan
3. inspect indexes
4. inspect data volume
5. identify lock contention
6. optimize query or schema
7. measure again

Do not automatically increase database instance size without understanding the bottleneck.

---

## 50. Database Design Checklist

Before introducing a table or major schema change, verify:

- Is the table responsibility clear?
- Is the primary key appropriate?
- Are business identifiers protected?
- Are unique constraints required?
- Are foreign keys required?
- Are indexes justified?
- Are composite indexes based on real queries?
- Is pagination required?
- Is JSON appropriate?
- Are timestamps clear?
- Is transaction behavior understood?
- Are concurrency risks considered?
- Is migration safe?
- Is rollback understood?
- Is retention defined?
- Is monitoring available?

---

## 51. Database Engineering Principle

The platform follows this principle:

> Protect data integrity at the database boundary, optimize based on measured workloads, and evolve schemas through safe, version-controlled, backward-compatible changes.

The database is not simply a storage layer.

It is a critical architectural boundary responsible for:

- integrity
- concurrency
- performance
- durability
- recovery
