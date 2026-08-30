# AWS Network and Security Architecture

## 1. Purpose

This document defines the production VPC, subnet, routing, and security-group architecture for the Solar Energy Monitoring Platform.

The primary objective is to expose only the application load balancer publicly while keeping compute and data services private.

---

## 2. Network Architecture

The production platform runs inside an Amazon VPC spanning at least two Availability Zones.


                           Internet
                              |
                              v
                       Internet Gateway
                              |
                +-------------+-------------+
                |                           |
                v                           v
          Public Subnet AZ-a          Public Subnet AZ-b
                |                           |
                +-----------+---------------+
                            |
                            v
                   Application Load
                       Balancer
                            |
                 +----------+----------+
                 |                     |
                 v                     v
          Private App AZ-a       Private App AZ-b
                 |                     |
              ECS API             ECS API
              ECS Worker          ECS Worker
                 |                     |
                 +----------+----------+
                            |
                            v
                 Private Data Subnets
                            |
             +--------------+--------------+
             |              |              |
             v              v              v
          RDS MySQL    RDS PostgreSQL   Redis




Target VPC
                         Internet
                            │
                            ▼
                    ┌───────────────┐
                    │ Internet GW   │
                    └───────┬───────┘
                            │
             ┌──────────────┴──────────────┐
             │           VPC               │
             │                             │
             │  ┌────────────────────────┐ │
             │  │ Public Subnets         │ │
             │  │                        │ │
             │  │   Application LB       │ │
             │  └───────────┬────────────┘ │
             │              │              │
             │  ┌───────────▼────────────┐ │
             │  │ Private App Subnets    │ │
             │  │                        │ │
             │  │ ECS API    ECS Worker  │ │
             │  └───────────┬────────────┘ │
             │              │              │
             │  ┌───────────▼────────────┐ │
             │  │ Private Data Subnets   │ │
             │  │                        │ │
             │  │ RDS MySQL              │ │
             │  │ RDS PostgreSQL         │ │
             │  │ ElastiCache Redis      │ │
             │  └────────────────────────┘ │
             │                             │
             └──────────────────────────────┘

