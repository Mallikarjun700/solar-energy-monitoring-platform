import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TelemetryEvent } from '../../models/telemetry-event.model';
import { TelemetryReadingsComponent } from './telemetry-readings';

describe('TelemetryReadingsComponent', () => {
  let fixture: ComponentFixture<TelemetryReadingsComponent>;
  let component: TelemetryReadingsComponent;

  const event: TelemetryEvent = {
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
      location: 'Block A',
    },
    payload: {
      power_kw: 125.4,
      temperature: 28.5,
    },
    createdAt: '2026-09-01T10:00:01Z',
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TelemetryReadingsComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(TelemetryReadingsComponent);
    component = fixture.componentInstance;
    component.events = [event];

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render the event ID', () => {
    expect(fixture.nativeElement.textContent).toContain(event.eventId);
  });

  it('should render the source ID', () => {
    expect(fixture.nativeElement.textContent).toContain(event.sourceId);
  });

  it('should render the event type', () => {
    expect(fixture.nativeElement.textContent).toContain(event.eventType);
  });

  it('should render the schema version', () => {
    expect(fixture.nativeElement.textContent).toContain(String(event.schemaVersion));
  });

  it('should render the event timestamps', () => {
    const times = fixture.nativeElement.querySelectorAll('time');

    expect(times).toHaveLength(2);
    expect(times[0].getAttribute('datetime')).toBe(event.eventTimestamp);
    expect(times[1].getAttribute('datetime')).toBe(event.receivedAt);
  });

  it('should start with details collapsed', () => {
    expect(component.isExpanded(event.id)).toBe(false);
    expect(fixture.nativeElement.textContent).not.toContain('power_kw');
  });

  it('should expand event details through the accessible button', () => {
    const button = fixture.nativeElement.querySelector(
      'button.details-button',
    ) as HTMLButtonElement;

    expect(button).not.toBeNull();
    expect(button.getAttribute('aria-expanded')).toBe('false');
    expect(button.textContent).toContain('View details');

    button.click();
    fixture.detectChanges();

    expect(component.isExpanded(event.id)).toBe(true);
    expect(fixture.nativeElement.textContent).toContain('power_kw');
    expect(fixture.nativeElement.textContent).toContain('temperature');

    expect(button.getAttribute('aria-expanded')).toBe('true');
    expect(button.textContent).toContain('Hide details');

    const detailsRow = fixture.nativeElement.querySelector('#telemetry-details-1') as HTMLElement;

    expect(detailsRow).not.toBeNull();
  });

  it('should collapse event details through the accessible button', () => {
    const button = fixture.nativeElement.querySelector(
      'button.details-button',
    ) as HTMLButtonElement;

    button.click();
    fixture.detectChanges();

    expect(component.isExpanded(event.id)).toBe(true);

    button.click();
    fixture.detectChanges();

    expect(component.isExpanded(event.id)).toBe(false);
    expect(fixture.nativeElement.textContent).not.toContain('power_kw');
    expect(button.getAttribute('aria-expanded')).toBe('false');
    expect(button.textContent).toContain('View details');
  });

  it('should expose the details relationship through ARIA attributes', () => {
    const button = fixture.nativeElement.querySelector(
      'button.details-button',
    ) as HTMLButtonElement;

    expect(button.getAttribute('aria-controls')).toBe('telemetry-details-1');
    expect(button.getAttribute('aria-label')).toContain(event.eventId);
  });

  it('should render attributes and payload after expansion', () => {
    component.toggleDetails(event.id);
    fixture.detectChanges();

    const details = fixture.nativeElement.querySelector('.details') as HTMLElement;

    expect(details.textContent).toContain('Attributes');
    expect(details.textContent).toContain('device_id');
    expect(details.textContent).toContain('location');
    expect(details.textContent).toContain('Payload');
    expect(details.textContent).toContain('power_kw');
    expect(details.textContent).toContain('temperature');
  });

  it('should format JSON payload', () => {
    const result = component.formatJson(event.payload);

    expect(result).toContain('"power_kw": 125.4');
  });

  it('should format JSON attributes', () => {
    const result = component.formatJson(event.attributes);

    expect(result).toContain('"device_id": 1');
    expect(result).toContain('"location": "Block A"');
  });

  it('should handle null JSON values', () => {
    expect(component.formatJson(null)).toBe('No data');
  });

  it('should handle empty JSON values', () => {
    expect(component.formatJson({})).toBe('No data');
  });

  it('should track events by database ID', () => {
    expect(component.trackByEventId(0, event)).toBe(event.id);
  });

  it('should only allow one event to be expanded at a time', () => {
    const secondEvent: TelemetryEvent = {
      ...event,
      id: 2,
      eventId: '650e8400-e29b-41d4-a716-446655440000',
    };

    component.events = [event, secondEvent];
    fixture.detectChanges();

    component.toggleDetails(event.id);
    expect(component.isExpanded(event.id)).toBe(true);

    component.toggleDetails(secondEvent.id);

    expect(component.isExpanded(event.id)).toBe(false);
    expect(component.isExpanded(secondEvent.id)).toBe(true);
  });
});
