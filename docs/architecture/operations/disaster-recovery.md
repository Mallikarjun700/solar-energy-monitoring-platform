# Disaster Recovery Architecture

## Purpose

This document defines the disaster recovery strategy for the Solar Energy
Monitoring Platform.

The goal is to restore critical platform functionality after infrastructure,
application, database, or availability-zone failures while minimizing data
loss.

---

## 1. Recovery Objectives

The platform uses Recovery Time Objective (RTO) and Recovery Point Objective
(RPO) targets for each major component.

| Component | RTO | RPO |
|---|---:|---:|
| API / application | 30 minutes | N/A |
| MySQL | 1 hour | 15 minutes |
| PostgreSQL telemetry | 1 hour | 5–15 minutes |
| Redis | 30 minutes | N/A |
| Queue processing | 30 minutes | 15 minutes |
| Dead Letter Queue | 1 hour | Near-zero / durable |
| Entire platform | 1 hour | 15 minutes |

---

## 2. Recovery Time Objective

RTO defines the maximum acceptable time required to restore service after a
major failure.

The target for the complete platform is one hour.

Application recovery is expected to be faster because ECS task replacement
and deployment are automated.

Database recovery may take longer because restoration depends on the
availability of backups and the size of the affected database.

---

## 3. Recovery Point Objective

RPO defines the maximum acceptable amount of data loss measured in time.

The target for critical persisted data is 15 minutes or less.

Telemetry data has a tighter target of 5–15 minutes because telemetry is the
primary source of historical monitoring information.

Redis does not have a strict RPO because it is treated as a recoverable
cache/transient data store rather than the system of record.

---

## 4. Data Criticality

### Critical

- Telemetry events
- Operational application data
- Dead Letter Queue events

These require durable storage and backup protection.

### Recoverable

- Redis cache
- Temporary application state
- ECS task instances

These can be recreated from the durable system of record.

---

## 5. Recovery Strategy

The recovery process follows these stages:

```text
Failure Detection
       |
       v
Assess Impact
       |
       v
Recover Infrastructure
       |
       v
Recover Databases
       |
       v
Deploy Application
       |
       v
Recover Queue Processing
       |
       v
Validate Telemetry Ingestion
       |
       v
Validate DLQ
       |
       v
Restore Normal Operations
