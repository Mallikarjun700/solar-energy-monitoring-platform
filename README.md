# Solar Energy Monitoring & Asset Management Platform

A portfolio project demonstrating the design and implementation of a scalable renewable energy monitoring and asset management platform.

## Purpose

The system is designed to monitor renewable energy assets, process device telemetry, provide operational dashboards, generate alerts, and support asset management workflows.

## Architecture Goals

- Scalability
- Availability
- Maintainability
- Security
- Performance
- Observability
- Fault tolerance

## Technology Stack

- Angular
- Laravel
- PHP
- MySQL
- Redis
- AWS
- Docker
- REST APIs
- Message Queues

## Project Status

🚧 Architecture and requirements
![High Level Architecture](diagrams/high-level-architecture.drawio.jpg)

## Infrastructure

Terraform infrastructure lives in [`infrastructure/terraform`](infrastructure/terraform).
The repo pins Terraform `1.16.0` in [`infrastructure/terraform/.terraform-version`](infrastructure/terraform/.terraform-version).
If Terraform is not installed locally, use:

```bash
brew tap hashicorp/tap
brew install hashicorp/tap/terraform
```

Then validate from the Terraform directory:

```bash
terraform init
terraform fmt -check
terraform validate
```

## Telemetry Reliability

The telemetry processing pipeline implements:

- Idempotency
- Retry with backoff
- Dead Letter Queue
- DLQ inspection
- DLQ replay
- Safe replay through idempotent processing

Architecture:
[Telemetry DLQ Architecture](docs/architecture/telemetry-dlq.md)
