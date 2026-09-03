# High Availability and Failure Scenarios

## Purpose

This document defines the high-availability architecture and expected system
behavior when infrastructure or application components fail.

The platform is designed to minimize single points of failure and recover
automatically where possible.

---

## 1. Availability Zones

The AWS infrastructure uses multiple Availability Zones.

Application subnets and data subnets are distributed across the configured
Availability Zones.

The goal is to prevent the failure of a single Availability Zone from
causing complete application unavailability.

---

## 2. Application Availability

The Laravel application runs as an ECS service behind an Application Load
Balancer.

```text
                 Application Load Balancer
                         |
                 +-------+-------+
                 |               |
              ECS Task        ECS Task
              Backend         Backend
