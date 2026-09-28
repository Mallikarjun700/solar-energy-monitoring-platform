# API Reference

Base path:

`/api/v1`

Authentication uses Laravel Sanctum. Protected endpoints require an authenticated token and, where specified, the required ability.

## Authentication

### Login

`POST /api/v1/auth/login`

Creates an authenticated session/token.

Request and response fields are defined by the current `AuthController` implementation. Do not hard-code credentials in examples or documentation.

### Current user

`GET /api/v1/auth/me`

Requires authentication.

### Logout

`POST /api/v1/auth/logout`

Requires authentication.

## Readiness

### Application readiness

`GET /api/v1/ready`

Public readiness endpoint used by deployment/runtime health checks.

Successful response:

```json
{
  "status": "ready",
  "checks": {
    "database": "ok",
    "queue": "ok"
  }
}
```

Failure returns HTTP 503.

## Dashboard

`GET /api/v1/dashboard`

Requires authentication.

Returns dashboard information used by the Angular dashboard.

## Plants

| Method | Endpoint | Ability |
|---|---|---|
| GET | `/plants` | `plants:read` |
| POST | `/plants` | `plants:write` |
| GET | `/plants/{plant}` | `plants:read` |

## Assets

| Method | Endpoint | Ability |
|---|---|---|
| GET | `/assets` | `assets:read` |
| POST | `/assets` | `assets:write` |
| GET | `/assets/{asset}` | `assets:read` |
| PUT | `/assets/{asset}` | `assets:write` |
| PATCH | `/assets/{asset}` | `assets:write` |
| DELETE | `/assets/{asset}` | `assets:write` |

## Devices

| Method | Endpoint | Ability |
|---|---|---|
| GET | `/devices` | `devices:read` |
| POST | `/devices` | `devices:write` |
| GET | `/devices/{device}` | `devices:read` |
| PUT | `/devices/{device}` | `devices:write` |
| PATCH | `/devices/{device}` | `devices:write` |
| DELETE | `/devices/{device}` | `devices:write` |

## Telemetry

### Ingest telemetry

`POST /api/v1/telemetry/events`

Required ability:

`telemetry:write`

Additional middleware:

- telemetry throttling
- request-size validation
- idempotency handling

The ingestion path is asynchronous and returns HTTP 202 when the batch is accepted for processing.

Conceptual request:

```json
{
  "events": [
    {
      "device_id": 123,
      "timestamp": "2026-09-28T10:00:00Z"
    }
  ]
}
```

The exact accepted telemetry schema is defined by the Laravel request validation and telemetry service implementation.

### Telemetry health

`GET /api/v1/telemetry/health`

Requires `telemetry:read`.

### Paginated telemetry

`GET /api/v1/telemetry/events`

Requires `telemetry:read`.

### Cursor-based telemetry

`GET /api/v1/telemetry/events/cursor`

Requires `telemetry:read`.

Use cursor pagination for large result sets where supported by the frontend/client.

### Latest device telemetry

`GET /api/v1/telemetry/devices/{deviceId}/latest`

Requires `telemetry:read`.

## Dead Letter Queue

### List DLQ records

`GET /api/v1/dlq`

Requires `dlq:read`.

### Replay a DLQ record

`POST /api/v1/dlq/{deadLetterEvent}/replay`

Requires `dlq:replay`.

Replay sends the event back through normal processing. Idempotency prevents a previously successful event from being persisted twice.

## Alerts

| Method | Endpoint | Ability |
|---|---|---|
| GET | `/alerts` | `alerts:read` |
| GET | `/alerts/{alert}` | `alerts:read` |
| POST | `/alerts/{alert}/acknowledge` | `alerts:acknowledge` |
| POST | `/alerts/{alert}/resolve` | `alerts:resolve` |

## Authorization model

The API has three layers:

1. Authentication — Laravel Sanctum identifies the caller.
2. Ability checks — endpoint middleware checks fine-grained abilities.
3. Role/resource authorization — application logic limits access to permitted resources.

A missing or invalid token should be treated as an authentication failure. A valid identity without the required ability is an authorization failure.

## Common HTTP statuses

| Status | Meaning |
|---|---|
| 200 | Request completed successfully |
| 201 | Resource created |
| 202 | Request accepted for asynchronous processing |
| 204 | Successful request with no response body, where applicable |
| 401 | Authentication required/failed |
| 403 | Authenticated but not authorized |
| 404 | Resource not found |
| 422 | Validation failed |
| 429 | Rate limit exceeded |
| 500 | Unexpected server error |
| 503 | Service/readiness failure |

## Source of truth

The definitive route list is maintained in:

`backend/routes/api.php`

Request validation and response schemas should be verified against the relevant controller/request/resource classes before treating this document as a schema contract.
