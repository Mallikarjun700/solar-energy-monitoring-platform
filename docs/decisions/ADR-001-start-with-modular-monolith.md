Title:
Start with Modular Monolith

Context:

The system contains multiple business domains including plants,
assets, devices, telemetry, alerts and analytics.

Decision:

The MVP will begin as a modular monolith.

Reason:

A modular monolith reduces operational complexity while allowing
clear domain boundaries.

The architecture will allow selected modules to be extracted
into independent services when scale or organizational boundaries
justify it.

Consequences:

The initial system is easier to develop, test and deploy.

High-volume workloads such as telemetry can later be separated
from synchronous business APIs.