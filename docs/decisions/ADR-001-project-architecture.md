Decision:

Build the platform using modular services with clear domain boundaries.

Reason:

The platform contains multiple business capabilities such as asset management,
telemetry processing, alerting and analytics.

The architecture should allow these capabilities to evolve independently.

Initial implementation:

Start as a modular monolith to reduce unnecessary operational complexity.

Future evolution:

Extract high-volume or independently scalable components into microservices.