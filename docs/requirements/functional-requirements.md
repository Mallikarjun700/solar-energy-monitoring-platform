# Functional Requirements

## Authentication and authorization

- **FR-001** Users must be able to log into the platform.
- **FR-002** Users must be authenticated before accessing protected APIs.
- **FR-003** The system must support role-based authorization.
- **FR-004** Users must only access resources permitted by their role.

## Plant management

- **FR-005** An administrator can create a solar plant.
- **FR-006** An administrator can update plant information.
- **FR-007** Users can view plants they are authorized to access.
- **FR-008** A plant must contain one or more assets.

## Asset management

- **FR-009** Users can register assets against a plant.
- **FR-010** Users can update asset information.
- **FR-011** Users can view asset status.
- **FR-012** Assets must be associated with a plant.

## Device management

- **FR-013** Users can register devices.
- **FR-014** Devices must belong to an asset.
- **FR-015** The system must track device status.
- **FR-016** The system must record the last communication time of a device.

## Telemetry

- **FR-017** The system must accept telemetry from devices.
- **FR-018** Telemetry must contain a device identifier and timestamp.
- **FR-019** The system must store telemetry data.
- **FR-020** Users can retrieve telemetry for a device.
- **FR-021** Users can view historical energy data.
- **FR-025** Users can view recent telemetry.

## Dashboard and operations

- **FR-022** Users can view plant-level KPIs.
- **FR-023** Users can view energy generation.
- **FR-024** Users can view device status.

## Implementation reference

These requirements describe intended platform capabilities. For the implemented API surface, see [API Reference](../api/api-reference.md).

For executable behavior, the implementation in `backend/` and `frontend/` is authoritative.
