# Production AWS Architecture

## 1. Purpose

This document defines the target production architecture for the Solar Energy Monitoring Platform.

The architecture separates API workloads, asynchronous telemetry processing, transactional application data, telemetry/event storage, caching, and long-term archival.

---

## 2. Architecture Goals

The production architecture must provide:

- horizontal application scaling
- asynchronous telemetry processing
- independent scaling of queue workers
- highly available relational databases
- Redis-based read optimization
- durable telemetry archival
- secure secret management
- centralized logging and monitoring
- health-based deployment verification
- controlled CI/CD promotion
- failure isolation
- operational observability

---

## 3. High-Level Architecture

```text
Internet
   |
   v
Route 53
   |
   v
Application Load Balancer
   |
   +-----------------------+
   |                       |
   v                       v
ECS/Fargate App       ECS/Fargate App
   |                       |
   +-----------+-----------+
               |
      +--------+--------+----------------+
      |                 |                |
      v                 v                v
 RDS MySQL        RDS PostgreSQL    ElastiCache
 Core Data        Telemetry Data      Redis
      |                 |
      |                 v
      |             S3 Archive
      |
      v
Application Services


Telemetry / Async Processing

Telemetry API
     |
     v
Queue
     |
     +------------------+
     |                  |
     v                  v
Worker 1            Worker 2
ECS/Fargate         ECS/Fargate
     |
     v
Telemetry Processing
     |
     +----------+-----------+
     |          |           |
     v          v           v
PostgreSQL   Redis       Alert Jobs