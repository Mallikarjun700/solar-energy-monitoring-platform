import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TelemetryEvent } from '../../models/telemetry-event.model';
import { TelemetryTrendComponent } from './telemetry-trend';

describe('TelemetryTrendComponent', () => {
  let fixture: ComponentFixture<TelemetryTrendComponent>;
  let component: TelemetryTrendComponent;

  const createEvent = (id: number, timestamp: string): TelemetryEvent => ({
    id,
    eventId: `event-${id}`,
    tenantId: 'tenant-1',
    sourceId: 'source-1',
    eventType: 'telemetry.power',
    eventTimestamp: timestamp,
    receivedAt: timestamp,
    schemaVersion: 1,
    attributes: {
      device_id: id,
    },
    payload: {
      power_kw: 100 + id,
    },
    createdAt: timestamp,
  });

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TelemetryTrendComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(TelemetryTrendComponent);
    component = fixture.componentInstance;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should produce no trend points when there are no events', () => {
    component.events = [];

    component.ngOnChanges({
      events: {
        currentValue: [],
        previousValue: [],
        firstChange: true,
        isFirstChange: () => true,
      },
    });

    expect(component.trendPoints).toEqual([]);
    expect(component.maxCount).toBe(0);
  });

  it('should create trend points from event timestamps', () => {
    component.events = [
      createEvent(1, '2026-09-01T10:00:00Z'),
      createEvent(2, '2026-09-01T10:01:00Z'),
      createEvent(3, '2026-09-01T10:05:00Z'),
    ];

    component.ngOnChanges({
      events: {
        currentValue: component.events,
        previousValue: [],
        firstChange: true,
        isFirstChange: () => true,
      },
    });

    expect(component.trendPoints.length).toBeGreaterThan(0);
    expect(component.maxCount).toBeGreaterThan(0);
  });

  it('should count events correctly when timestamps are identical', () => {
    const timestamp = '2026-09-01T10:00:00Z';

    component.events = [
      createEvent(1, timestamp),
      createEvent(2, timestamp),
      createEvent(3, timestamp),
    ];

    component.ngOnChanges({
      events: {
        currentValue: component.events,
        previousValue: [],
        firstChange: true,
        isFirstChange: () => true,
      },
    });

    expect(component.trendPoints).toHaveLength(1);
    expect(component.trendPoints[0].count).toBe(3);
    expect(component.maxCount).toBe(3);
    expect(component.trendPoints[0].percentage).toBe(100);
  });

  it('should calculate percentages relative to the largest bucket', () => {
    component.events = [
      createEvent(1, '2026-09-01T10:00:00Z'),
      createEvent(2, '2026-09-02T10:00:00Z'),
    ];

    component.ngOnChanges({
      events: {
        currentValue: component.events,
        previousValue: [],
        firstChange: true,
        isFirstChange: () => true,
      },
    });

    expect(component.trendPoints.length).toBeGreaterThan(0);

    for (const point of component.trendPoints) {
      expect(point.percentage).toBeGreaterThan(0);
      expect(point.percentage).toBeLessThanOrEqual(100);
    }
  });

  it('should keep event counts independent of payload values', () => {
    component.events = [
      {
        ...createEvent(1, '2026-09-01T10:00:00Z'),
        payload: {
          power_kw: 999999,
        },
      },
      {
        ...createEvent(2, '2026-09-01T10:00:00Z'),
        payload: {
          power_kw: 1,
        },
      },
    ];

    component.ngOnChanges({
      events: {
        currentValue: component.events,
        previousValue: [],
        firstChange: true,
        isFirstChange: () => true,
      },
    });

    expect(component.trendPoints).toHaveLength(1);
    expect(component.trendPoints[0].count).toBe(2);
  });
});
