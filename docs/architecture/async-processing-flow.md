Client
  │
  ▼
Telemetry API
  │
  │ dispatch
  ▼
┌──────────────────────────┐
│ ProcessTelemetryBatchJob │
└────────────┬─────────────┘
             │
             ▼
       ┌───────────┐
       │   Queue   │
       │  (DB)     │
       └─────┬─────┘
             │
             │ queue:work
             ▼
       ┌───────────┐
       │  Worker   │
       └─────┬─────┘
             │
             ▼
    TelemetryService
             │
       ┌─────┴─────┐
       │           │
       ▼           ▼
 Idempotency    Processing
       │           │
       └─────┬─────┘
             ▼
      Telemetry DB
             │
             │ failure
             ▼
          Retry
             │
       retry exhausted
             │
             ▼
            DLQ