import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';

import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { ErrorState } from '../../shared/components/error-state/error-state';
import { LoadingState } from '../../shared/components/loading-state/loading-state';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { DEFAULT_TELEMETRY_FILTERS } from './models/telemetry-filters.model';
import { TelemetryEvent } from './models/telemetry-event.model';
import { TelemetryPaginationMeta } from './models/telemetry-response.model';
import { TelemetryService } from './services/telemetry.service';
import { TelemetryFiltersComponent } from './components/telemetry-filters/telemetry-filters';
import { TelemetryReadingsComponent } from './components/telemetry-readings/telemetry-readings';
import { TelemetrySummaryComponent } from './components/telemetry-summary/telemetry-summary';
import { TelemetryTrendComponent } from './components/telemetry-trend/telemetry-trend';
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
        PageHeader,
        LoadingState,
        EmptyState,
        ErrorState,
        TelemetryFiltersComponent,
        TelemetrySummaryComponent,
        TelemetryReadingsComponent,
        TelemetryTrendComponent,
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

  it('should finish initial loading after a successful request', () => {
    expect(component.initialLoading()).toBe(false);
    expect(component.loading()).toBe(false);
    expect(component.refreshing()).toBe(false);
  });

  it('should expose loaded events', () => {
    expect(component.events()).toEqual(events);
    expect(component.hasEvents()).toBe(true);
  });

  it('should render the telemetry trend when events are available', () => {
    expect(fixture.nativeElement.querySelector('app-telemetry-trend')).not.toBeNull();
    expect(fixture.nativeElement.textContent).toContain('Telemetry trend');
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
      from: '2026-09-01',
      to: '2026-09-07',
    });

    component.applyFilters();

    expect(telemetryService.getEvents).toHaveBeenLastCalledWith({
      sourceId: undefined,
      eventType: undefined,
      from: '2026-09-01',
      to: '2026-09-07',
      perPage: 50,
      page: 1,
    });
  });

  it('should clear filters and reload page one', () => {
    component.updateFilters({
      sourceId: 'source-123',
      eventType: 'telemetry.power',
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

  it('should navigate to the next page', () => {
    telemetryService.getEvents.mockReturnValueOnce(
      of({
        events,
        pagination,
      }),
    );

    telemetryService.getEvents.mockReturnValueOnce(
      of({
        events,
        pagination: {
          ...pagination,
          current_page: 2,
          from: 51,
          to: 51,
        },
      }),
    );

    component.loadEvents();
    telemetryService.getEvents.mockClear();

    component.goToNextPage();

    expect(telemetryService.getEvents).toHaveBeenCalledWith(
      expect.objectContaining({
        page: 2,
      }),
    );

    expect(component.currentPage()).toBe(2);
  });

  it('should not navigate beyond the last page', () => {
    component.pagination.set({
      ...pagination,
      current_page: 2,
    });

    telemetryService.getEvents.mockClear();

    component.goToNextPage();

    expect(telemetryService.getEvents).not.toHaveBeenCalled();
  });

  it('should navigate to the previous page when available', () => {
    component.pagination.set({
      ...pagination,
      current_page: 2,
    });

    telemetryService.getEvents.mockClear();

    component.goToPreviousPage();

    expect(telemetryService.getEvents).toHaveBeenCalledWith(
      expect.objectContaining({
        page: 1,
      }),
    );
  });

  it('should not navigate before the first page', () => {
    telemetryService.getEvents.mockClear();

    component.goToPreviousPage();

    expect(telemetryService.getEvents).not.toHaveBeenCalled();
  });

  it('should expose active filter state', () => {
    expect(component.hasActiveFilters()).toBe(false);

    component.updateFilters({
      sourceId: 'source-123',
    });

    expect(component.hasActiveFilters()).toBe(true);
  });

  it('should count date filters as active filters', () => {
    expect(component.hasActiveFilters()).toBe(false);

    component.updateFilters({
      from: '2026-09-01',
    });

    expect(component.hasActiveFilters()).toBe(true);
  });

  it('should expose previous-page state', () => {
    component.pagination.set({
      ...pagination,
      current_page: 1,
    });

    expect(component.hasPreviousPage()).toBe(false);

    component.pagination.set({
      ...pagination,
      current_page: 2,
    });

    expect(component.hasPreviousPage()).toBe(true);
  });

  it('should expose next-page state', () => {
    component.pagination.set({
      ...pagination,
      current_page: 1,
      last_page: 2,
    });

    expect(component.hasNextPage()).toBe(true);

    component.pagination.set({
      ...pagination,
      current_page: 2,
      last_page: 2,
    });

    expect(component.hasNextPage()).toBe(false);
  });

  it('should handle an API error', () => {
    telemetryService.getEvents.mockReturnValue(
      throwError(() => ({
        message: 'Unable to reach the server.',
      })),
    );

    component.loadEvents();

    expect(component.loading()).toBe(false);
    expect(component.initialLoading()).toBe(false);
    expect(component.refreshing()).toBe(false);
    expect(component.error()).toBe('Unable to reach the server.');
  });

  it('should allow retry after an error', () => {
    telemetryService.getEvents
      .mockReturnValueOnce(
        throwError(() => ({
          message: 'Unable to reach the server.',
        })),
      )
      .mockReturnValueOnce(
        of({
          events,
          pagination,
        }),
      );

    component.loadEvents();

    expect(component.error()).toBe('Unable to reach the server.');

    component.retry();

    expect(component.error()).toBe(null);
    expect(component.events()).toEqual(events);
  });

  it('should handle an empty result', () => {
    telemetryService.getEvents.mockReturnValue(
      of({
        events: [],
        pagination: {
          ...pagination,
          from: null,
          to: null,
          total: 0,
          last_page: 1,
        },
      }),
    );

    component.loadEvents();

    expect(component.events()).toEqual([]);
    expect(component.hasEvents()).toBe(false);
    expect(component.error()).toBe(null);

    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('app-telemetry-trend')).toBeNull();
  });

  it('should not navigate while a request is loading', () => {
    telemetryService.getEvents.mockClear();
    component.loading.set(true);

    component.goToNextPage();

    expect(telemetryService.getEvents).not.toHaveBeenCalled();
  });
});
