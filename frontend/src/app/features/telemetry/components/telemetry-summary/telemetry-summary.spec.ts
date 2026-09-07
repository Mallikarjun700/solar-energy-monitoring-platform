import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DEFAULT_TELEMETRY_FILTERS, TelemetryFilters } from '../../models/telemetry-filters.model';
import { TelemetryPaginationMeta } from '../../models/telemetry-response.model';
import { TelemetrySummaryComponent } from './telemetry-summary';

describe('TelemetrySummaryComponent', () => {
  let fixture: ComponentFixture<TelemetrySummaryComponent>;
  let component: TelemetrySummaryComponent;

  const pagination: TelemetryPaginationMeta = {
    current_page: 2,
    from: 51,
    last_page: 4,
    links: [],
    path: '/api/v1/telemetry/events',
    per_page: 50,
    to: 100,
    total: 175,
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TelemetrySummaryComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(TelemetrySummaryComponent);
    component = fixture.componentInstance;

    component.pagination = pagination;
    component.filters = {
      ...DEFAULT_TELEMETRY_FILTERS,
    };

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should expose total event count', () => {
    expect(component.totalEvents).toBe(175);
  });

  it('should expose current page information', () => {
    expect(component.currentPage).toBe(2);
    expect(component.lastPage).toBe(4);
  });

  it('should expose current result range', () => {
    expect(component.resultFrom).toBe(51);
    expect(component.resultTo).toBe(100);
  });

  it('should expose page size', () => {
    expect(component.pageSize).toBe(50);
  });

  it('should report no active filters', () => {
    expect(component.activeFilterCount).toBe(0);
    expect(component.filterDescription).toBe('All available telemetry events');
  });

  it('should count active filters', () => {
    const filters: TelemetryFilters = {
      ...DEFAULT_TELEMETRY_FILTERS,
      sourceId: 'source-1',
      eventType: 'telemetry.power',
      from: '2026-09-01T00:00',
    };

    component.filters = filters;

    expect(component.activeFilterCount).toBe(3);
    expect(component.filterDescription).toBe('3 active filters');
  });

  it('should use configured page size when pagination is unavailable', () => {
    component.pagination = null;

    expect(component.pageSize).toBe(DEFAULT_TELEMETRY_FILTERS.perPage);
  });

  it('should handle missing pagination values safely', () => {
    component.pagination = null;

    expect(component.totalEvents).toBe(0);
    expect(component.currentPage).toBe(1);
    expect(component.lastPage).toBe(1);
    expect(component.resultFrom).toBe(0);
    expect(component.resultTo).toBe(0);
  });
});
