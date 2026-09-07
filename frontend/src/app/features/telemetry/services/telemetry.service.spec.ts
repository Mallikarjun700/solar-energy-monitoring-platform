import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { describe, expect, beforeEach, afterEach, it } from 'vitest';

import { ApiErrorService } from '../../../core/services/api-error.service';
import { environment } from '../../../../environments/environment';
import { TelemetryService } from './telemetry.service';

describe('TelemetryService', () => {
  let service: TelemetryService;
  let httpTesting: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting(), ApiErrorService],
    });

    service = TestBed.inject(TelemetryService);
    httpTesting = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpTesting.verify();
  });

  it('creates the service', () => {
    expect(service).toBeTruthy();
  });

  it('loads telemetry events without filters', () => {
    let result: unknown;

    service.getEvents().subscribe((response) => {
      result = response;
    });

    const request = httpTesting.expectOne(`${environment.apiBaseUrl}/telemetry/events`);

    expect(request.request.method).toBe('GET');
    expect(request.request.params.keys()).toEqual([]);

    request.flush({
      current_page: 1,
      data: [
        {
          id: 1,
          event_id: 'event-1',
          tenant_id: 'tenant-1',
          source_id: 'source-1',
          event_type: 'telemetry.power',
          event_timestamp: '2026-09-06T10:00:00Z',
          received_at: '2026-09-06T10:00:01Z',
          schema_version: 1,
          attributes: {
            device_id: 1,
          },
          payload: {
            power_kw: 125.5,
          },
          created_at: '2026-09-06T10:00:01Z',
        },
      ],
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
    });

    expect(result).toEqual({
      events: [
        {
          id: 1,
          eventId: 'event-1',
          tenantId: 'tenant-1',
          sourceId: 'source-1',
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
        },
      ],
      pagination: {
        current_page: 1,
        from: 1,
        last_page: 1,
        links: [],
        path: '/api/v1/telemetry/events',
        per_page: 50,
        to: 1,
        total: 1,
      },
    });
  });

  it('sends supported telemetry query parameters', () => {
    service
      .getEvents({
        tenantId: 'tenant-1',
        sourceId: 'source-1',
        eventType: 'telemetry.power',
        from: '2026-09-01',
        to: '2026-09-06',
        perPage: 100,
      })
      .subscribe();

    const request = httpTesting.expectOne(
      `${environment.apiBaseUrl}/telemetry/events?tenant_id=tenant-1&source_id=source-1&event_type=telemetry.power&from=2026-09-01&to=2026-09-06&per_page=100`,
    );

    expect(request.request.method).toBe('GET');

    expect(request.request.params.get('tenant_id')).toBe('tenant-1');
    expect(request.request.params.get('source_id')).toBe('source-1');
    expect(request.request.params.get('event_type')).toBe('telemetry.power');
    expect(request.request.params.get('from')).toBe('2026-09-01');
    expect(request.request.params.get('to')).toBe('2026-09-06');
    expect(request.request.params.get('per_page')).toBe('100');

    request.flush({
      current_page: 1,
      data: [],
      first_page_url: '/api/v1/telemetry/events?page=1',
      from: null,
      last_page: 1,
      last_page_url: '/api/v1/telemetry/events?page=1',
      links: [],
      next_page_url: null,
      path: '/api/v1/telemetry/events',
      per_page: 100,
      prev_page_url: null,
      to: null,
      total: 0,
    });
  });

  it('sends the requested page parameter', () => {
    service
      .getEvents({
        perPage: 50,
        page: 3,
      })
      .subscribe();

    const request = httpTesting.expectOne(
      `${environment.apiBaseUrl}/telemetry/events?per_page=50&page=3`,
    );

    expect(request.request.method).toBe('GET');
    expect(request.request.params.get('page')).toBe('3');

    request.flush({
      current_page: 3,
      data: [],
      first_page_url: '/api/v1/telemetry/events?page=1',
      from: null,
      last_page: 3,
      last_page_url: '/api/v1/telemetry/events?page=3',
      links: [],
      next_page_url: null,
      path: '/api/v1/telemetry/events',
      per_page: 50,
      prev_page_url: '/api/v1/telemetry/events?page=2',
      to: null,
      total: 0,
    });
  });

  it('does not send empty optional query parameters', () => {
    service
      .getEvents({
        tenantId: '',
        sourceId: '',
        eventType: '',
        from: '',
        to: '',
        perPage: 50,
      })
      .subscribe();

    const request = httpTesting.expectOne(`${environment.apiBaseUrl}/telemetry/events?per_page=50`);

    expect(request.request.params.keys()).toEqual(['per_page']);

    request.flush({
      current_page: 1,
      data: [],
      first_page_url: '/api/v1/telemetry/events?page=1',
      from: null,
      last_page: 1,
      last_page_url: '/api/v1/telemetry/events?page=1',
      links: [],
      next_page_url: null,
      path: '/api/v1/telemetry/events',
      per_page: 50,
      prev_page_url: null,
      to: null,
      total: 0,
    });
  });

  it('loads cursor-based telemetry events', () => {
    let result: unknown;

    service
      .getEventsCursor({
        perPage: 100,
        cursor: 'cursor-123',
      })
      .subscribe((response) => {
        result = response;
      });

    const request = httpTesting.expectOne(
      `${environment.apiBaseUrl}/telemetry/events/cursor?per_page=100&cursor=cursor-123`,
    );

    expect(request.request.method).toBe('GET');
    expect(request.request.params.get('per_page')).toBe('100');
    expect(request.request.params.get('cursor')).toBe('cursor-123');

    request.flush({
      data: [
        {
          id: 2,
          event_id: 'event-2',
          tenant_id: 'tenant-1',
          source_id: 'source-1',
          event_type: 'telemetry.power',
          event_timestamp: '2026-09-06T11:00:00Z',
          received_at: '2026-09-06T11:00:01Z',
          schema_version: 1,
          attributes: null,
          payload: {
            power_kw: 140,
          },
          created_at: '2026-09-06T11:00:01Z',
        },
      ],
      path: '/api/v1/telemetry/events/cursor',
      per_page: 100,
      next_cursor: 'cursor-456',
      next_page_url: '/api/v1/telemetry/events/cursor?cursor=cursor-456',
      prev_cursor: null,
      prev_page_url: null,
    });

    expect(result).toEqual({
      events: [
        {
          id: 2,
          eventId: 'event-2',
          tenantId: 'tenant-1',
          sourceId: 'source-1',
          eventType: 'telemetry.power',
          eventTimestamp: '2026-09-06T11:00:00Z',
          receivedAt: '2026-09-06T11:00:01Z',
          schemaVersion: 1,
          attributes: null,
          payload: {
            power_kw: 140,
          },
          createdAt: '2026-09-06T11:00:01Z',
        },
      ],
      nextCursor: 'cursor-456',
      hasMore: true,
    });
  });

  it('returns no cursor when cursor pagination is exhausted', () => {
    let result: unknown;

    service.getEventsCursor().subscribe((response) => {
      result = response;
    });

    const request = httpTesting.expectOne(`${environment.apiBaseUrl}/telemetry/events/cursor`);

    request.flush({
      data: [],
      path: '/api/v1/telemetry/events/cursor',
      per_page: 50,
      next_cursor: null,
      next_page_url: null,
      prev_cursor: null,
      prev_page_url: null,
    });

    expect(result).toEqual({
      events: [],
      nextCursor: null,
      hasMore: false,
    });
  });

  it('loads latest telemetry for a device', () => {
    let result: unknown;

    service.getLatest(123, '550e8400-e29b-41d4-a716-446655440000').subscribe((response) => {
      result = response;
    });

    const request = httpTesting.expectOne(
      `${environment.apiBaseUrl}/telemetry/devices/123/latest?tenant_id=550e8400-e29b-41d4-a716-446655440000`,
    );

    expect(request.request.method).toBe('GET');
    expect(request.request.params.get('tenant_id')).toBe('550e8400-e29b-41d4-a716-446655440000');

    request.flush({
      event_id: 'event-latest',
      event_type: 'telemetry.power',
      timestamp: '2026-09-06T12:00:00Z',
      attributes: {
        device_id: 123,
      },
      payload: {
        power_kw: 150,
      },
    });

    expect(result).toEqual({
      eventId: 'event-latest',
      eventType: 'telemetry.power',
      timestamp: '2026-09-06T12:00:00Z',
      attributes: {
        device_id: 123,
      },
      payload: {
        power_kw: 150,
      },
    });
  });

  it('normalizes event API errors', () => {
    let error: unknown;

    service.getEvents().subscribe({
      error: (value) => {
        error = value;
      },
    });

    const request = httpTesting.expectOne(`${environment.apiBaseUrl}/telemetry/events`);

    request.flush(
      {
        message: 'Telemetry query failed.',
      },
      {
        status: 500,
        statusText: 'Server Error',
      },
    );

    expect(error).toMatchObject({
      message: 'Telemetry query failed.',
      status: 500,
    });
  });

  it('normalizes latest telemetry not found errors', () => {
    let error: unknown;

    service.getLatest(999, 'tenant-1').subscribe({
      error: (value) => {
        error = value;
      },
    });

    const request = httpTesting.expectOne(
      `${environment.apiBaseUrl}/telemetry/devices/999/latest?tenant_id=tenant-1`,
    );

    request.flush(
      {
        message: 'Latest telemetry not found.',
      },
      {
        status: 404,
        statusText: 'Not Found',
      },
    );

    expect(error).toMatchObject({
      message: 'Latest telemetry not found.',
      status: 404,
    });
  });

  it('normalizes network errors', () => {
    let error: unknown;

    service.getEvents().subscribe({
      error: (value) => {
        error = value;
      },
    });

    const request = httpTesting.expectOne(`${environment.apiBaseUrl}/telemetry/events`);

    request.error(new ProgressEvent('error'), {
      status: 0,
      statusText: 'Unknown Error',
    });

    expect(error).toMatchObject({
      message: 'Unable to reach the server.',
      status: 0,
    });
  });
});
