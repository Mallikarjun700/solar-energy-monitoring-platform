# Non-Functional Requirements & Scalability Assumptions

This document outlines the performance, availability, and throughput design targets for the Solar Energy Monitoring & Asset Management Platform.

> Note: These targets serve as architectural design goals to demonstrate scale and distributed systems engineering, rather than claims about existing production systems.

---

## 1. Non-Functional Requirements

### Availability
* Target Availability: 99.9%
* Maximum Allowed Downtime: ~8.76 hours per year (planned and unplanned combined)

### API Performance
* Normal Read APIs: p95 < 300 ms
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

## 3. Ingestion Throughput

Assuming each device transmits telemetry data once every 10 seconds:

Throughput = 1,000,000 devices / 10 seconds = 100,000 events/sec

---

## 4. Daily Telemetry Projections

Extrapolating the peak ingestion rate yields the following load estimates:

* Per Minute: 100,000 x 60 = 6,000,000 events
* Per Hour: 6,000,000 x 60 = 360,000,000 events
* Per Day: 360,000,000 x 24 = 8,640,000,000 events/day

(~8.64 Billion events per day)

---

## 5. Architectural Implications

Handling 100,000 events/sec and 8.64B daily writes transitions this system into a distributed infrastructure problem requiring:

1. Message Queues & Streaming: Decoupling ingestion from processing (e.g., AWS SQS / Kafka).
2. Worker Pools: Scalable, horizontally partitioned consumers to process data off the queue.
3. Partitioned Storage: Distributed time-series databases or sharded storage layers optimized for write-heavy loads.
4. Caching & Aggregations: Caching real-time operational states and pre-aggregating metrics for dashboard read models.