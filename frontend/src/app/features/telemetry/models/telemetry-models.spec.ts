import { describe, expect, it } from 'vitest';

import { DEFAULT_TELEMETRY_FILTERS, TelemetryFilters } from './telemetry-filters.model';
import { TelemetryEvent } from './telemetry-event.model';
import { TelemetryApiResource, TelemetryEventsApiResponse } from './telemetry-response.model';
import { TelemetryLatest, TelemetryLatestApiResponse } from './telemetry-latest.model';

describe('Telemetry models', () => {
  it('defines the default telemetry filters', () => {
    expect(DEFAULT_TELEMETRY_FILTERS).toEqual({
      tenantId: '',
      sourceId: '',
      eventType: '',
      from: '',
      to: '',
      perPage: 50,
    });
  });

  it('supports the telemetry event frontend model', () => {
    const event: TelemetryEvent = {
      id: 1,
      eventId: '550e8400-e29b-41d4-a716-446655440000',
      tenantId: '550e8400-e29b-41d4-a716-446655440001',
      sourceId: '550e8400-e29b-41d4-a716-446655440002',
      eventType: 'telemetry.power',
      eventTimestamp: '2026-09-06T10:00:00Z',
      receivedAt: '2026-09-06T10:00:01Z',
      schemaVersion: 1,
      attributes: {
        device_id: 1,
      },
      payload: {
        power_kw: 125.5,
      },
      createdAt: '2026-09-06T10:00:01Z',
    };

    expect(event.eventType).toBe('telemetry.power');
    expect(event.payload?.['power_kw']).toBe(125.5);
  });

  it('supports backend pagination resources', () => {
    const resource: TelemetryApiResource = {
      id: 1,
      event_id: 'event-1',
      tenant_id: 'tenant-1',
      source_id: 'source-1',
      event_type: 'telemetry.power',
      event_timestamp: '2026-09-06T10:00:00Z',
      received_at: '2026-09-06T10:00:01Z',
      schema_version: 1,
      attributes: null,
      payload: {
        power_kw: 100,
      },
      created_at: '2026-09-06T10:00:01Z',
    };

    const response: TelemetryEventsApiResponse = {
      current_page: 1,
      data: [resource],
      first_page_url: '/api/v1/telemetry/events?page=1',
      from: 1,
      last_page: 1,
      last_page_url: '/api/v1/telemetry/events?page=1',
      links: [],
      next_page_url: null,
      path: '/api/v1/telemetry/events',
      per_page: 50,
      prev_page_url: null,
      to: 1,
      total: 1,
    };

    expect(response.data).toHaveLength(1);
    expect(response.total).toBe(1);
  });

  it('supports latest telemetry responses', () => {
    const apiResponse: TelemetryLatestApiResponse = {
      event_id: 'event-1',
      event_type: 'telemetry.power',
      timestamp: '2026-09-06T10:00:00Z',
      attributes: {
        device_id: 1,
      },
      payload: {
        power_kw: 100,
      },
    };

    const latest: TelemetryLatest = {
      eventId: apiResponse.event_id,
      eventType: apiResponse.event_type,
      timestamp: apiResponse.timestamp,
      attributes: apiResponse.attributes,
      payload: apiResponse.payload,
    };

    expect(latest.eventId).toBe('event-1');
    expect(latest.payload?.['power_kw']).toBe(100);
  });

  it('supports partial filter updates', () => {
    const filters: TelemetryFilters = {
      ...DEFAULT_TELEMETRY_FILTERS,
      eventType: 'telemetry.power',
      perPage: 100,
    };

    expect(filters.eventType).toBe('telemetry.power');
    expect(filters.perPage).toBe(100);
  });
});
