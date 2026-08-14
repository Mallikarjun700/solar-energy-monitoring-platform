# Non-Functional Requirements & Scalability Assumptions

This document outlines the performance, availability, scalability, and storage design targets for the **Solar Energy Monitoring & Asset Management Platform**.

> Note: These metrics represent portfolio architectural design targets to demonstrate scale and distributed systems design, rather than operational claims of existing production systems.

---

## 1. Non-Functional Requirements

### Availability & Resilience
* Target Availability: 99.9%
* Maximum Allowed Downtime: ~8.76 hours per year (planned and unplanned combined)
* Recovery Point Objective (RPO): < 5 minutes for relational operational data; < 1 hour for historical telemetry logs.
* Recovery Time Objective (RTO): < 1 hour for critical services.

### API Performance Targets
* Standard Read APIs: p95 < 300 ms
* Telemetry Ingestion API: p95 < 500 ms

---

## 2. Scalability Assumptions

To establish baseline architectural constraints, the initial design target is modeled using the following device topology:

* Plants: 1,000 plants
* Assets per Plant: 100 assets / plant
* Total Assets: 1,000 x 100 = 100,000 assets
* Devices per Asset: 10 devices / asset
* Total Devices: 100,000 x 10 = 1,000,000 devices

---

## 3. Ingestion Throughput & Data Estimates

Assuming each device transmits telemetry data once every 10 seconds:

* Ingestion Throughput: 1,000,000 devices / 10 seconds = 100,000 events/sec

### Daily Telemetry Projections
* Per Minute: 100,000 x 60 = 6,000,000 events
* Per Hour: 6,000,000 x 60 = 360,000,000 events
* Per Day: 360,000,000 x 24 = 8,640,000,000 events/day (~8.64 Billion events/day)

---

## 4. Storage & Capacity Planning

Assuming an average telemetry payload size of **~200 bytes** per compressed event:

* Daily Storage Requirement: 8.64B events x 200 bytes ≈ 1.728 TB / day
* Monthly Raw Volume: ~52 TB / month
* Yearly Raw Volume: ~630 TB / year

---

## 5. Storage, Archival & Backup Strategy

Given the large write volume, data is categorized into three storage tiers to balance cost and performance:

### Data Tiering Policy
1. Hot Tier (0 – 7 Days):
   * Storage: Managed Time-Series DB / Sharded Cluster (e.g., TimescaleDB, InfluxDB, or DynamoDB).
   * Purpose: High-speed ingestion, real-time dashboard rendering, immediate alert generation.
2. Warm Tier (8 – 90 Days):
   * Storage: Aggregated Columnar Storage (e.g., AWS Redshift, Snowflake, or ClickHouse).
   * Purpose: Downsampled historical trend analysis, performance reporting, and analytics.
3. Cold / Archival Tier (90+ Days):
   * Storage: Object Storage with Glacier Lifecycle Policies (e.g., AWS S3 Glacier / Parquet format).
   * Purpose: Long-term compliance, auditing, and batch ML training.

---

## 6. Backup & Disaster Recovery (DR) Plan

### Operational Data (MySQL / Relational DB)
* Automated Backups: Daily automated full database snapshots.
* Point-In-Time Recovery (PITR): Transaction logs (WAL / Binlogs) backed up continuously every 5 minutes.
* Geographic Redundancy: Multi-AZ primary deployment with cross-region read replicas for disaster recovery.

### Telemetry & Analytical Data
* Immutable Object Store: Parquet files stored in S3 with Object Lock enabled (Write Once, Read Many) to prevent accidental deletion or ransomware modification.
* Lifecycle Rules: Automatic transition of raw telemetry logs to S3 Glacier Deep Archive after 90 days.

---

## 7. Architectural Implications

Handling 100,000 events/sec and 8.64B daily writes transitions this system into a distributed infrastructure problem requiring:

1. Message Queues & Streaming: Decoupling ingestion from processing (e.g., AWS SQS / Kafka).
2. Worker Pools: Scalable, horizontally partitioned consumers to process data off the queue.
3. Partitioned Storage: Distributed time-series databases or sharded storage layers optimized for write-heavy loads.
4. Caching & Aggregations: Caching real-time operational states and pre-aggregating metrics for dashboard read models.