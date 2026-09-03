# Backup and Restore Strategy

## Purpose

This document defines backup, retention, restoration, and recovery validation
for the Solar Energy Monitoring Platform.

Backups protect critical application and telemetry data and provide a recovery
mechanism for infrastructure and data-store failures.

---

## 1. Backup Strategy

The platform separates durable data from recoverable infrastructure.

| Component | Backup Strategy | Recovery Source |
|---|---|---|
| MySQL | Automated RDS backups | RDS backup / snapshot |
| PostgreSQL | Automated RDS backups | RDS backup / snapshot |
| Dead Letter Queue | Durable database storage | Database backup |
| Redis | No primary-data dependency | Recreate / repopulate |
| ECR images | Immutable Git SHA images | ECR |
| Terraform | Remote state + version control | Terraform state / Git |
| Application code | Git repository | Git |
| Configuration | Terraform + GitHub configuration | Source control / GitHub |

---

## 2. MySQL Backup

MySQL stores operational application data.

The backup strategy should include:

- Automated RDS backups.
- Point-in-time recovery where supported by the configured retention.
- Manual snapshots before major production changes.
- Protection of production snapshots from accidental deletion.

The latest backup that satisfies the RPO should be selected during recovery.

### Restore Process

1. Identify the required recovery point.
2. Restore the RDS instance or snapshot.
3. Verify database availability.
4. Verify schema and migrations.
5. Validate application connectivity.
6. Validate application-critical queries.
7. Resume normal application traffic.

---

## 3. PostgreSQL Telemetry Backup

PostgreSQL stores telemetry data and therefore has a higher data-protection
priority.

The backup strategy should include:

- Automated RDS backups.
- Point-in-time recovery where supported.
- Manual snapshots before major changes.
- Retention appropriate for the defined telemetry recovery requirements.

### Restore Process

1. Identify the recovery point.
2. Restore the PostgreSQL database.
3. Verify telemetry tables and indexes.
4. Validate recent telemetry records.
5. Verify application connectivity.
6. Validate telemetry ingestion.
7. Verify duplicate protection and idempotency.
8. Resume normal processing.

---

## 4. Dead Letter Queue Backup

The Dead Letter Queue contains failed events that may require investigation
or replay.

DLQ data is treated as durable data.

The backup must preserve:

- Event ID
- Original payload
- Error type
- Failure reason
- Attempt count
- First failure timestamp
- Last failure timestamp
- Status

DLQ recovery must preserve the ability to inspect and safely replay failed
events.

---

## 5. Redis Recovery

Redis is not treated as the system of record.

The platform should remain recoverable if Redis data is lost.

Recovery consists of:

1. Recreate or restore Redis.
2. Verify network connectivity.
3. Verify authentication and encryption configuration.
4. Restart affected application services if required.
5. Rebuild cache contents.
6. Verify application functionality.

Business-critical and telemetry data must not exist exclusively in Redis.

---

## 6. ECR Image Recovery

Container images use immutable Git commit SHA tags.

Example:

```text
backend:<git-sha>
nginx:<git-sha>
