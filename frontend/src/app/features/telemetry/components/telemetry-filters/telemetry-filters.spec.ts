import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DEFAULT_TELEMETRY_FILTERS } from '../../models/telemetry-filters.model';
import { TelemetryFiltersComponent } from './telemetry-filters';

describe('TelemetryFiltersComponent', () => {
  let fixture: ComponentFixture<TelemetryFiltersComponent>;
  let component: TelemetryFiltersComponent;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TelemetryFiltersComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(TelemetryFiltersComponent);
    component = fixture.componentInstance;
    component.filters = {
      ...DEFAULT_TELEMETRY_FILTERS,
    };

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should report no date error when dates are empty', () => {
    expect(component.hasDateRangeError).toBe(false);
  });

  it('should report no date error for a valid range', () => {
    component.filters = {
      ...DEFAULT_TELEMETRY_FILTERS,
      from: '2026-09-01T00:00',
      to: '2026-09-02T00:00',
    };

    expect(component.hasDateRangeError).toBe(false);
  });

  it('should report a date error when from is after to', () => {
    component.filters = {
      ...DEFAULT_TELEMETRY_FILTERS,
      from: '2026-09-03T00:00',
      to: '2026-09-02T00:00',
    };

    expect(component.hasDateRangeError).toBe(true);
  });

  it('should emit filter changes', () => {
    const emitSpy = vi.spyOn(component.filtersChange, 'emit');

    component.updateField('sourceId', 'source-123');

    expect(emitSpy).toHaveBeenCalledWith({
      sourceId: 'source-123',
    });
  });

  it('should emit apply for a valid filter range', () => {
    const emitSpy = vi.spyOn(component.apply, 'emit');

    component.filters = {
      ...DEFAULT_TELEMETRY_FILTERS,
      from: '2026-09-01T00:00',
      to: '2026-09-02T00:00',
    };

    component.applyFilters();

    expect(emitSpy).toHaveBeenCalled();
  });

  it('should not emit apply for an invalid filter range', () => {
    const emitSpy = vi.spyOn(component.apply, 'emit');

    component.filters = {
      ...DEFAULT_TELEMETRY_FILTERS,
      from: '2026-09-03T00:00',
      to: '2026-09-02T00:00',
    };

    component.applyFilters();

    expect(emitSpy).not.toHaveBeenCalled();
  });

  it('should emit clear', () => {
    const emitSpy = vi.spyOn(component.clear, 'emit');

    component.clearFilters();

    expect(emitSpy).toHaveBeenCalled();
  });
});
