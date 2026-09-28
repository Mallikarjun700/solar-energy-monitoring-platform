# Plant → Asset → Device Flow

The platform models physical solar infrastructure as a hierarchy:

```text
Plant
  |
  +-- Asset
       |
       +-- Device
            |
            +-- Telemetry
```

## Plant

A plant represents a solar generation site.

Users can:

- list plants
- create plants when authorized
- view a specific plant

Plant-level data establishes the parent context for downstream assets.

## Asset

An asset belongs to a plant.

Examples of physical assets can include inverter systems, trackers, transformers, or other plant equipment.

Users can:

- list assets
- create assets
- view assets
- update assets
- delete assets

Asset operations require the appropriate `assets:read` or `assets:write` ability.

## Device

A device belongs to an asset.

A device represents a telemetry-producing sensor, meter, controller, or edge unit.

Users can:

- list devices
- create devices
- view devices
- update devices
- delete devices

Device operations require `devices:read` or `devices:write`.

## Telemetry relationship

Telemetry is associated with a device identifier.

The relationship allows the platform to navigate:

`Plant → Asset → Device → Telemetry`

This hierarchy is important for dashboards, historical analysis, device health, and operational troubleshooting.

## Typical lifecycle

1. Administrator creates a plant.
2. An asset is registered under the plant.
3. One or more devices are registered under the asset.
4. The device sends telemetry.
5. Telemetry is processed asynchronously.
6. Operators view device and plant information.
7. Alerts can be acknowledged and resolved as operational conditions change.

## Failure considerations

A missing parent relationship should prevent invalid resource creation.

Telemetry processing should not infer a device relationship from arbitrary payload data; the device identity must be validated according to the backend's telemetry rules.

## Related API

See [API Reference](../api/api-reference.md).
