# Alert Flow

Alerts represent operational conditions that require attention.

## Lifecycle

```text
Condition / Telemetry
        |
        v
      Alert
        |
        +--> Acknowledge
        |
        v
      Resolve
```

## Alert retrieval

Authenticated users with `alerts:read` can:

- list alerts
- retrieve an individual alert

Endpoints:

`GET /api/v1/alerts`

`GET /api/v1/alerts/{alert}`

## Acknowledge

An authorized operator acknowledges an alert using:

`POST /api/v1/alerts/{alert}/acknowledge`

Required ability:

`alerts:acknowledge`

Acknowledgement indicates that the operational team has seen the alert and accepted it for handling.

## Resolve

An authorized operator resolves an alert using:

`POST /api/v1/alerts/{alert}/resolve`

Required ability:

`alerts:resolve`

Resolution closes the operational lifecycle for the alert according to the backend implementation.

## Why acknowledgement and resolution are separate

The states represent different operational actions:

- **Open** — condition requires attention.
- **Acknowledged** — someone has taken ownership/seen it.
- **Resolved** — the issue has been handled or the alert condition has been closed.

Keeping acknowledgement separate from resolution preserves operational history and avoids treating "seen" as "fixed".

## Failure considerations

Alert operations must remain authorized even when the underlying condition originated from telemetry processing.

Telemetry ingestion and alert state management should not be coupled to a single synchronous request path.

## Related API

See [API Reference](../api/api-reference.md).
