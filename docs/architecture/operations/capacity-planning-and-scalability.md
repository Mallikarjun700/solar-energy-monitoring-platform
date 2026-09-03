# Capacity Planning and Scalability Operations

## 1. Purpose

The Solar Energy Monitoring Platform is designed to handle increasing:

- solar assets
- devices
- telemetry events
- API requests
- queue workloads
- database records
- dashboard users

Capacity planning ensures that the platform can scale before infrastructure becomes a bottleneck.

The goal is to maintain:

- predictable performance
- availability
- processing reliability
- acceptable latency
- controlled infrastructure cost

---

## 2. Capacity Planning Principles

The platform follows these principles:

1. Scale horizontally where possible.
2. Keep API services stateless.
3. Separate synchronous API traffic from asynchronous processing.
4. Scale queue workers independently from API services.
5. Monitor database capacity continuously.
6. Use Redis for appropriate high-speed workloads.
7. Identify bottlenecks before they become outages.
8. Prefer measured scaling over permanent over-provisioning.
9. Load-test important workloads before major capacity increases.
10. Review capacity as telemetry volume grows.

---

## 3. Major Capacity Drivers

The primary capacity drivers are:

### API Traffic

- telemetry ingestion requests
- dashboard requests
- asset management APIs
- authentication requests
- administrative operations

### Telemetry Volume

Telemetry is expected to be the largest continuously growing workload.

Important dimensions include:

- events per second
- events per minute
- events per day
- average event size
- batch size
- number of devices
- number of solar assets

### Asynchronous Processing

Queue capacity depends on:

- number of queued jobs
- job processing time
- worker concurrency
- retry rate
- failure rate
- DLQ growth

### Database Growth

Database capacity is affected by:

- telemetry records
- asset records
- users
- historical data
- indexes
- query frequency
- storage growth

---

## 4. Capacity Model

A basic telemetry capacity model is:

    Daily Events =
        Devices × Events Per Device Per Day

For example:

    10,000 devices
    × 1 event/minute
    × 1,440 minutes/day

    = 14,400,000 events/day

This number should be used as an input to:

- database sizing
- storage planning
- queue capacity planning
- worker capacity planning
- network capacity planning

Actual production capacity must be validated through load testing and monitoring.

---

## 5. API Scalability

The API layer runs on ECS.

API services should remain stateless so multiple ECS tasks can process requests independently.

Architecture:

    Internet
       ↓
      ALB
       ↓
    ┌───────┬───────┬───────┐
    │ ECS 1 │ ECS 2 │ ECS 3 │
    └───────┴───────┴───────┘

Horizontal scaling is preferred over continuously increasing the resources of a single task.

---

## 6. API Scaling Signals

Important scaling signals include:

- CPU utilization
- memory utilization
- request count
- request latency
- ALB target response time
- HTTP 5xx rate
- ECS task health
- concurrent requests

Scaling should consider multiple signals rather than relying on CPU alone.

For example:

    High request volume
          +
    High CPU
          +
    Increasing latency
          ↓
    Scale ECS API tasks

---

## 7. Queue Worker Scalability

Telemetry processing is asynchronous.

Architecture:

    API
     ↓
    Queue
     ↓
    Queue Workers
     ↓
    Telemetry Database

API capacity and processing capacity are therefore independently scalable.

This prevents slow telemetry processing from directly blocking API requests.

---

## 8. Queue Capacity

Queue capacity should be evaluated using:

- queue depth
- oldest job age
- job processing duration
- jobs processed per minute
- retry rate
- worker utilization
- failed jobs
- DLQ growth

A growing queue indicates that incoming workload is exceeding processing capacity.

---

## 9. Queue Scaling Model

If:

    Incoming Jobs > Processing Capacity

then queue depth increases.

Scaling workers increases processing capacity:

    More Queue Workers
            ↓
    More Concurrent Processing
            ↓
    Queue Drain Rate Increases

The objective is to maintain acceptable queue latency rather than simply maximizing worker count.

---

## 10. Queue Backlog Example

Suppose:

    Incoming rate = 1,000 jobs/minute
    Processing rate = 800 jobs/minute

Backlog increases by:

    200 jobs/minute

If the system scales workers so that:

    Processing rate = 1,200 jobs/minute

then the backlog can begin decreasing.

This model should be validated using real processing-time measurements.

---

## 11. Telemetry Batch Size

Telemetry ingestion supports batching.

Batching reduces:

- HTTP request overhead
- database round trips
- queue overhead
- serialization overhead

However, excessively large batches can increase:

- memory consumption
- request duration
- failure impact
- retry payload size

Therefore, batch limits should be explicitly defined and load-tested.

---

## 12. Database Capacity Planning

The platform uses separate database workloads for:

- application data
- telemetry data

This separation prevents high-volume telemetry writes from directly competing with application transactions.

Capacity planning should track:

- CPU
- memory
- storage
- IOPS
- connections
- query latency
- slow queries
- replication/availability requirements
- table/index growth

---

## 13. MySQL Capacity

MySQL supports the core application workload.

Monitor:

- CPU utilization
- storage utilization
- connection count
- query latency
- slow queries
- lock contention
- transaction throughput

When sustained resource pressure occurs, evaluate:

1. query optimization
2. index optimization
3. connection management
4. application caching
5. instance right-sizing
6. read scaling where appropriate
7. architectural changes

Scaling infrastructure should not be the first response to an inefficient query.

---

## 14. PostgreSQL Telemetry Capacity

The telemetry database handles high-volume telemetry data.

Important metrics include:

- write throughput
- storage growth
- query latency
- connection count
- CPU
- IOPS
- index size
- table size

Telemetry data growth should be forecast based on:

    Devices
    ×
    Events/device/day
    ×
    Average event size
    ×
    Retention period

---

## 15. Storage Growth

Telemetry storage grows continuously.

Capacity planning should estimate:

    Storage Required
      =
    Daily Data Growth
      ×
    Retention Period
      +
    Index Overhead
      +
    Operational Headroom

Operational headroom should be maintained to avoid emergency storage exhaustion.

---

## 16. Data Retention

Long-term telemetry retention should be explicitly defined.

Possible strategies include:

- hot data in PostgreSQL
- archival storage for older data
- aggregation of historical telemetry
- deletion according to retention policy

Older high-volume telemetry should not be allowed to grow indefinitely in the primary transactional database without a capacity strategy.

---

## 17. Redis Capacity

Redis is used for low-latency application workloads.

Capacity planning should monitor:

- memory utilization
- connection count
- command latency
- cache hit rate
- evictions
- CPU
- network throughput

If Redis memory approaches capacity:

1. review key expiration policies
2. remove unnecessary cached data
3. optimize cache usage
4. evaluate larger nodes
5. evaluate sharding where appropriate

Persistent business data must not depend solely on Redis.

---

## 18. ALB Capacity

The Application Load Balancer distributes incoming traffic across healthy ECS tasks.

Monitor:

- request count
- target response time
- HTTP 4xx
- HTTP 5xx
- target health
- connection behavior

ALB capacity should be evaluated together with ECS capacity.

Increasing ALB capacity alone does not solve an overloaded application layer.

---

## 19. ECS Task Sizing

Each ECS service should be sized based on measured workload.

Important parameters:

- CPU
- memory
- desired task count
- minimum task count
- maximum task count
- startup time
- health-check time

Example:

    API:
      Higher CPU/memory requirements

    Queue Worker:
      Optimized for processing throughput

    Scheduler:
      Lower steady-state workload

Different services should not automatically use identical task sizes.

---

## 20. Horizontal vs Vertical Scaling

### Horizontal Scaling

Increase the number of ECS tasks.

Advantages:

- improved availability
- distributes workload
- supports rolling deployments
- avoids dependence on one large task

### Vertical Scaling

Increase CPU or memory per task.

Useful when:

- individual requests require more resources
- workload cannot efficiently parallelize
- memory pressure exists
- application processing is CPU-intensive

Preferred strategy:

> Use horizontal scaling first where the workload is parallelizable, and vertical scaling when individual tasks require additional resources.

---

## 21. Auto Scaling

Auto scaling should be based on measurable demand.

Potential policies:

### CPU-Based

Scale when sustained CPU exceeds a threshold.

### Memory-Based

Scale when memory utilization remains high.

### Request-Based

Scale based on request volume or ALB request count per target.

### Queue-Based

Scale queue workers based on:

- queue depth
- oldest message age
- processing latency

Queue-based scaling is particularly important for telemetry workloads.

---

## 22. Scaling Safety

Scaling policies should include:

- minimum task count
- maximum task count
- cooldown/stabilization
- health checks
- deployment safety
- resource quotas

Without limits, an unexpected traffic spike could create uncontrolled infrastructure consumption.

---

## 23. Capacity Headroom

Production systems should maintain capacity headroom.

Headroom provides time to react when:

- traffic suddenly increases
- a deployment temporarily increases resource consumption
- a downstream dependency slows down
- one task or availability zone becomes unavailable

The exact headroom percentage should be determined using historical workload data and load testing rather than an arbitrary universal value.

---

## 24. Load Testing

Before major production scale increases, test:

- API throughput
- telemetry batch ingestion
- queue processing
- database writes
- database reads
- Redis operations
- concurrent dashboard requests

Load tests should identify:

- maximum sustainable throughput
- latency at different loads
- resource saturation points
- queue backlog behavior
- failure behavior

---

## 25. Capacity Test Scenarios

Important scenarios include:

### Normal Load

Expected production workload.

### Peak Load

Expected maximum business workload.

### Burst Load

Sudden telemetry or API traffic increase.

### Sustained Load

High traffic maintained for an extended period.

### Failure Load

One dependency becomes slow or unavailable.

### Recovery Load

Traffic returns while previously accumulated queue backlog is being processed.

---

## 26. Queue Recovery Capacity

A particularly important scenario is backlog recovery.

Example:

    Normal processing:
    1,000 jobs/minute

    Incident backlog:
    100,000 jobs

After recovery, workers should process:

    New incoming jobs
        +
    Existing backlog

The system must therefore have sufficient processing capacity to prevent the backlog from growing indefinitely.

---

## 27. Capacity Exhaustion Response

When a capacity limit is approaching:

1. identify the saturated resource
2. determine whether traffic is expected or abnormal
3. scale the appropriate service
4. reduce unnecessary load
5. optimize expensive operations
6. monitor recovery
7. investigate the root cause

Do not immediately scale every component.

Scale the actual bottleneck.

---

## 28. Capacity Forecasting

Capacity should be reviewed periodically using:

- telemetry growth
- device growth
- API traffic
- database storage growth
- queue throughput
- ECS utilization
- Redis utilization

Example forecast:

    Current:
    5,000 devices

    Expected:
    10,000 devices

    Future:
    25,000 devices

The architecture should be evaluated before each major growth milestone.

---

## 29. Growth Review Triggers

A capacity review should be triggered when:

- device count increases significantly
- telemetry volume increases significantly
- sustained CPU exceeds expected range
- memory pressure increases
- database storage approaches planned limits
- queue latency increases
- Redis memory approaches capacity
- API latency increases
- a major customer is onboarded
- a new telemetry source is introduced

---

## 30. Capacity Dashboard

A capacity dashboard should provide a single operational view of:

### API

- request rate
- latency
- error rate
- ECS CPU
- ECS memory
- task count

### Queue

- queue depth
- oldest job age
- processing rate
- retry rate
- DLQ count

### Databases

- CPU
- storage
- connections
- latency
- IOPS

### Redis

- memory
- connections
- latency
- evictions

### ALB

- request count
- target latency
- 4xx
- 5xx
- unhealthy targets

---

## 31. Cost and Capacity Trade-Off

Scaling improves capacity but increases cost.

The platform therefore evaluates:

    Performance
       +
    Availability
       +
    Scalability
       +
    Cost

rather than optimizing only one dimension.

For example:

- permanently running many ECS tasks may reduce scaling latency but increase cost
- aggressive auto scaling can reduce latency but increase infrastructure spend
- larger database instances can improve performance but may be inefficient if utilization is low

Capacity decisions should therefore be supported by measurements.

---

## 32. Capacity Planning Review

Capacity should be reviewed regularly.

The review should answer:

1. Are we approaching any infrastructure limits?
2. Is telemetry growing faster than expected?
3. Is queue processing keeping up with ingestion?
4. Are database writes keeping up with telemetry?
5. Is storage growth within forecast?
6. Are ECS tasks appropriately sized?
7. Are auto-scaling policies behaving correctly?
8. Are there persistent bottlenecks?
9. Is additional infrastructure required?
10. Can optimization delay infrastructure expansion?

---

## 33. Operational Principle

The platform should not wait for capacity exhaustion before scaling.

The operating principle is:

> Measure current utilization, forecast future demand, test expected peak workloads, and scale the bottleneck before it becomes an availability problem.

Capacity planning is therefore a continuous operational process rather than a one-time infrastructure decision.
