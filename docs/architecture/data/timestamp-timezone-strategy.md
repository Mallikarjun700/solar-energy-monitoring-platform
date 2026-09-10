# Timestamp & Timezone Strategy

## Policy

The Solar Energy Monitoring Platform uses UTC as the canonical
application and persistence timezone.

## Storage

All event timestamps represent an absolute instant.

PostgreSQL telemetry timestamps use timezone-aware timestamp types.

MySQL timestamps are written and interpreted by the application
using UTC.

## API

API timestamps use ISO-8601 representations containing timezone
information.

Example:

2026-09-03T07:05:40Z

## Frontend

The Angular application receives canonical UTC timestamps and
converts them only for presentation.

The frontend must not rewrite or persist timestamps in a local
timezone.

## Plant Timezones

A future plant configuration should contain an IANA timezone,
for example:

Asia/Kolkata
Europe/Berlin
America/New_York

Plant-local reporting periods must be converted to UTC query
boundaries before querying the database.

## Day Boundaries

Dashboard concepts such as "today" must eventually use the
plant timezone rather than assuming UTC midnight.

## Daylight Saving Time

Named IANA timezones must be used instead of fixed UTC offsets
when a plant operates in a region with daylight-saving rules.

## Rules

1. Store absolute event time in UTC.
2. Never infer timezone from the browser/server location.
3. Do not store local timestamps without timezone semantics.
4. Convert only at presentation/reporting boundaries.
5. Use explicit timezone boundaries for reporting queries.