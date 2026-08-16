                    Telemetry
                        │
                        ▼
                 Batch Ingestion
                        │
                        ▼
                  Processing
                        │
                  ┌─────┴─────┐
                  │           │
               Success      Failure
                  │           │
                  ▼           ▼
              Telemetry   Retryable?
                             │
                       ┌─────┴─────┐
                       │           │
                      Yes          No
                       │           │
                       ▼           ▼
                     Retry        DLQ
                       │           │
                 Max retries       │
                       │           │
                       └─────┬─────┘
                             ▼
                            DLQ
                             │
                     ┌───────┴───────┐
                     │               │
                  Inspect          Replay
                                     │
                                     ▼
                              Normal Processing
                                     │
                                     ▼
                                Idempotency
                                     │
                              ┌──────┴──────┐
                              │             │
                           New Event    Existing Event
                              │             │
                              ▼             ▼
                           Insert         Ignore
                              │             │
                              └──────┬──────┘
                                     ▼
                                  Resolved