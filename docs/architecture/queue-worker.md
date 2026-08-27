# Queue Worker Operations

## Worker

The telemetry platform processes telemetry asynchronously using Laravel's
database queue.

Start a worker with:

```bash
./scripts/queue-worker.sh