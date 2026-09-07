import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';

import { EmptyStateComponent } from '../../shared/components/empty-state/empty-state';
import { ErrorStateComponent } from '../../shared/components/error-state/error-state';
import { LoadingStateComponent } from '../../shared/components/loading-state/loading-state';
import { PageHeaderComponent } from '../../shared/components/page-header/page-header';
import { DEFAULT_TELEMETRY_FILTERS, TelemetryFilters } from './models/telemetry-filters.model';
import { TelemetryEvent } from './models/telemetry-event.model';
import { TelemetryPaginationMeta } from './models/telemetry-response.model';
import { TelemetryService } from './services/telemetry.service';
import { TelemetryFiltersComponent } from './components/telemetry-filters/telemetry-filters';
import { TelemetryComponent } from './telemetry.component';

describe('TelemetryComponent', () => {
  let fixture: ComponentFixture<TelemetryComponent>;
  let component: TelemetryComponent;

  const events: TelemetryEvent[] = [
    {
      id: 1,
      eventId: '550e8400-e29b-41d4-a716-446655440000',
      tenantId: 'tenant-1',
      sourceId: 'source-1',
      eventType: 'telemetry.power',
      eventTimestamp: '2026-09-01T10:00:00Z',
      receivedAt: '2026-09-01T10:00:01Z',
      schemaVersion: 1,
      attributes: {
        device_id: 1,
      },
      payload: {
        power_kw: 125.4,
      },
      createdAt: '2026-09-01T10:00:01Z',
    },
  ];

  const pagination: TelemetryPaginationMeta = {
    current_page: 1,
    from: 1,
    last_page: 2,
    links: [],
    path: '/api/v1/telemetry/events',
    per_page: 50,
    to: 1,
    total: 51,
  };

  let telemetryService: {
    getEvents: ReturnType<typeof vi.fn>;
  };

  beforeEach(async () => {
    telemetryService = {
      getEvents: vi.fn().mockReturnValue(
        of({
          events,
          pagination,
        }),
      ),
    };

    await TestBed.configureTestingModule({
      imports: [
        TelemetryComponent,
        PageHeaderComponent,
        LoadingStateComponent,
        EmptyStateComponent,
        ErrorStateComponent,
        TelemetryFiltersComponent,
      ],
      providers: [
        {
          provide: TelemetryService,
          useValue: telemetryService,
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(TelemetryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should load telemetry events on initialization', () => {
    expect(telemetryService.getEvents).toHaveBeenCalledWith({
      sourceId: undefined,
      eventType: undefined,
      from: undefined,
      to: undefined,
      perPage: 50,
      page: 1,
    });
  });

  it('should apply source and event type filters', () => {
    component.updateFilters({
      sourceId: 'source-123',
      eventType: 'telemetry.power',
    });

    component.applyFilters();

    expect(telemetryService.getEvents).toHaveBeenLastCalledWith({
      sourceId: 'source-123',
      eventType: 'telemetry.power',
      from: undefined,
      to: undefined,
      perPage: 50,
      page: 1,
    });
  });

  it('should apply date filters', () => {
    component.updateFilters({
      from: '2026-09-01T00:00',
      to: '2026-09-05T23:59',
    });

    component.applyFilters();

    expect(telemetryService.getEvents).toHaveBeenLastCalledWith({
      sourceId: undefined,
      eventType: undefined,
      from: '2026-09-01T00:00',
      to: '2026-09-05T23:59',
      perPage: 50,
      page: 1,
    });
  });

  it('should clear filters and reload the first page', () => {
    component.updateFilters({
      sourceId: 'source-123',
      eventType: 'telemetry.power',
      from: '2026-09-01T00:00',
      to: '2026-09-05T23:59',
    });

    component.clearFilters();

    expect(component.filters()).toEqual(DEFAULT_TELEMETRY_FILTERS);

    expect(telemetryService.getEvents).toHaveBeenLastCalledWith({
      sourceId: undefined,
      eventType: undefined,
      from: undefined,
      to: undefined,
      perPage: 50,
      page: 1,
    });
  });

  it('should reset pagination to page one when filters are applied', () => {
    component.updateFilters({
      sourceId: 'source-123',
    });

    component.applyFilters();

    expect(telemetryService.getEvents).toHaveBeenLastCalledWith(
      expect.objectContaining({
        page: 1,
      }),
    );
  });

  it('should navigate to the next page', () => {
    component.goToNextPage();

    expect(telemetryService.getEvents).toHaveBeenLastCalledWith(
      expect.objectContaining({
        page: 2,
      }),
    );
  });

  it('should expose active filter state', () => {
    expect(component.hasActiveFilters()).toBe(false);

    component.updateFilters({
      sourceId: 'source-123',
    });

    expect(component.hasActiveFilters()).toBe(true);
  });

  it('should expose loaded events', () => {
    expect(component.events()).toEqual(events);
    expect(component.hasEvents()).toBe(true);
  });

  it('should handle service errors', () => {
    telemetryService.getEvents.mockReturnValue(
      throwError(() => ({
        message: 'Unable to reach the server.',
      })),
    );

    component.loadEvents();

    expect(component.loading()).toBe(false);
    expect(component.error()).toBe('Unable to reach the server.');
    expect(component.events()).toEqual([]);
  });
});
