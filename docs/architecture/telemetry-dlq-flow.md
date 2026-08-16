# Telemetry DLQ Flow

                    ┌──────────────┐
                    │   Telemetry  │
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │Batch Ingest  │
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │  Processing  │
                    └──────┬───────┘
                           │
                    ┌──────┴───────┐
                    │              │
                 Success         Failure
                    │              │
                    ▼              ▼
                Completed      Retryable?
                                  │
                           ┌──────┴──────┐
                           │             │
                          Yes            No
                           │             │
                           ▼             ▼
                         Retry          DLQ
                           │             ▲
                           ▼             │
                    Max retries?         │
                           │             │
                           └──────Yes────┘
                                         │
                                         ▼
                                   ┌──────────┐
                                   │   DLQ    │
                                   └────┬─────┘
                                        │
                              ┌─────────┴─────────┐
                              │                   │
                           Inspect              Replay
                              │                   │
                              │                   ▼
                              │          Normal Processing
                              │                   │
                              │                   ▼
                              │             Idempotency
                              │                   │
                              │             ┌─────┴─────┐
                              │             │           │
                              │           New        Existing
                              │             │           │
                              │             ▼           ▼
                              │          Insert       Ignore
                              │             │           │
                              │             └─────┬─────┘
                              │                   ▼
                              │                Resolved
                              │
                              ▼
                          Investigation