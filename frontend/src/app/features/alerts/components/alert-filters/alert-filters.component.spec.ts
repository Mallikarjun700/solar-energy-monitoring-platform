import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AlertFiltersComponent } from './alert-filters.component';
import { DEFAULT_ALERT_FILTERS } from '../../models/alert-filters.model';

describe('AlertFiltersComponent', () => {
  let component: AlertFiltersComponent;
  let fixture: ComponentFixture<AlertFiltersComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AlertFiltersComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(AlertFiltersComponent);
    component = fixture.componentInstance;

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render all filter controls', () => {
    expect(fixture.nativeElement.querySelector('#alert-status')).toBeTruthy();

    expect(fixture.nativeElement.querySelector('#alert-severity')).toBeTruthy();

    expect(fixture.nativeElement.querySelector('#alert-type')).toBeTruthy();

    expect(fixture.nativeElement.querySelector('#alert-device-id')).toBeTruthy();

    expect(fixture.nativeElement.querySelector('#alert-rule-id')).toBeTruthy();

    expect(fixture.nativeElement.querySelector('#alert-from')).toBeTruthy();

    expect(fixture.nativeElement.querySelector('#alert-to')).toBeTruthy();

    expect(fixture.nativeElement.querySelector('#alert-per-page')).toBeTruthy();
  });

  it('should expose only API-supported status options', () => {
    const options = Array.from(fixture.nativeElement.querySelectorAll('#alert-status option')).map(
      (option: unknown) => (option as Element).getAttribute('value'),
    );

    expect(options).toEqual(['', 'open', 'acknowledged', 'resolved']);
  });

  it('should expose only API-supported severity options', () => {
    const options = Array.from(
      fixture.nativeElement.querySelectorAll('#alert-severity option'),
    ).map((option: unknown) => (option as Element).getAttribute('value'));

    expect(options).toEqual(['', 'info', 'warning', 'critical', 'emergency']);
  });

  it('should initialize with default filters', () => {
    expect(component.filters).toEqual(DEFAULT_ALERT_FILTERS);
  });

  it('should emit filters when applied', () => {
    const emitted: unknown[] = [];

    component.filtersApplied.subscribe((filters) => {
      emitted.push(filters);
    });

    component.filters = {
      status: 'open',
      severity: 'critical',
      alertType: '  temperature  ',
      deviceId: ' 42 ',
      ruleId: ' 7 ',
      from: '2026-09-01',
      to: '2026-09-08',
      perPage: 50,
    };

    component.apply();

    expect(emitted).toEqual([
      {
        status: 'open',
        severity: 'critical',
        alertType: 'temperature',
        deviceId: '42',
        ruleId: '7',
        from: '2026-09-01',
        to: '2026-09-08',
        perPage: 50,
      },
    ]);
  });

  it('should reset filters when cleared', () => {
    const emitted: unknown[] = [];

    component.filtersCleared.subscribe((filters) => {
      emitted.push(filters);
    });

    component.filters = {
      status: 'resolved',
      severity: 'warning',
      alertType: 'temperature',
      deviceId: '10',
      ruleId: '5',
      from: '2026-09-01',
      to: '2026-09-08',
      perPage: 25,
    };

    component.clear();

    expect(component.filters).toEqual(DEFAULT_ALERT_FILTERS);

    expect(emitted).toEqual([DEFAULT_ALERT_FILTERS]);
  });

  it('should render accessible labels', () => {
    const labels = Array.from(fixture.nativeElement.querySelectorAll('label')).map(
      (label: unknown) => (label as HTMLLabelElement).htmlFor,
    );

    expect(labels).toEqual([
      'alert-status',
      'alert-severity',
      'alert-type',
      'alert-device-id',
      'alert-rule-id',
      'alert-from',
      'alert-to',
      'alert-per-page',
    ]);
  });

  it('should enforce the alert type maximum length', () => {
    const input = fixture.nativeElement.querySelector('#alert-type') as HTMLInputElement;

    expect(input.maxLength).toBe(100);
  });
});
