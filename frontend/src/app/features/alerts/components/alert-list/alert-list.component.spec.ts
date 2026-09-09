import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AlertListComponent } from './alert-list.component';
import { Alert } from '../../models/alert.model';

describe('AlertListComponent', () => {
  let component: AlertListComponent;
  let fixture: ComponentFixture<AlertListComponent>;

  const alerts: Alert[] = [
    {
      id: 1,
      tenantId: 'tenant-1',
      plantId: 1,
      assetId: 2,
      deviceId: 10,
      ruleId: 20,
      eventId: null,
      alertType: 'temperature',
      severity: 'critical',
      status: 'open',
      message: 'Temperature exceeded threshold',
      triggeredAt: '2026-09-08T10:00:00Z',
      acknowledgedAt: null,
      resolvedAt: null,
      createdAt: '2026-09-08T10:00:00Z',
      updatedAt: '2026-09-08T10:00:00Z',
    },
    {
      id: 2,
      tenantId: 'tenant-1',
      plantId: null,
      assetId: null,
      deviceId: null,
      ruleId: 21,
      eventId: null,
      alertType: 'grid',
      severity: 'warning',
      status: 'acknowledged',
      message: 'Grid connection warning',
      triggeredAt: '2026-09-08T11:00:00Z',
      acknowledgedAt: '2026-09-08T11:05:00Z',
      resolvedAt: null,
      createdAt: '2026-09-08T11:00:00Z',
      updatedAt: '2026-09-08T11:05:00Z',
    },
  ];

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AlertListComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(AlertListComponent);
    component = fixture.componentInstance;

    fixture.componentRef.setInput('alerts', alerts);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render one table row per alert', () => {
    const rows = fixture.nativeElement.querySelectorAll('tbody tr');

    expect(rows.length).toBe(2);
  });

  it('should render alert type and message', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('temperature');
    expect(text).toContain('Temperature exceeded threshold');
    expect(text).toContain('grid');
    expect(text).toContain('Grid connection warning');
  });

  it('should render severity and status', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Critical');
    expect(text).toContain('Open');
    expect(text).toContain('Warning');
    expect(text).toContain('Acknowledged');
  });

  it('should render device and rule IDs', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Device #10');
    expect(text).toContain('Rule #20');
    expect(text).toContain('Rule #21');
  });

  it('should render an em dash for missing device IDs', () => {
    const secondRow = fixture.nativeElement.querySelectorAll('tbody tr')[1];

    expect(secondRow.textContent).toContain('—');
  });

  it('should render accessible table headers', () => {
    const headers = Array.from(fixture.nativeElement.querySelectorAll('th')).map(
      (header: unknown) => (header as Element).textContent?.trim(),
    );

    expect(headers).toEqual(['Alert', 'Severity', 'Status', 'Device', 'Rule', 'Triggered', 'Actions']);
  });

  it('should emit the selected alert', () => {
    const selected: Alert[] = [];

    component.alertSelected.subscribe((alert) => {
      selected.push(alert);
    });

    const button = fixture.nativeElement.querySelector('.details-button') as HTMLButtonElement;

    button.click();

    expect(selected).toEqual([alerts[0]]);
  });

  it('should provide an accessible label for each view action', () => {
    const buttons = Array.from(
      fixture.nativeElement.querySelectorAll('.details-button'),
    ) as HTMLButtonElement[];

    expect(buttons[0].getAttribute('aria-label')).toBe('View alert 1');

    expect(buttons[1].getAttribute('aria-label')).toBe('View alert 2');
  });

  it('should render an empty state when there are no alerts', () => {
    fixture.componentRef.setInput('alerts', []);
    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('.empty-list')).toBeTruthy();

    expect(fixture.nativeElement.querySelector('table')).toBeNull();
  });

  it('should preserve the original timestamp as datetime attribute', () => {
    const time = fixture.nativeElement.querySelector('time');

    expect(time.getAttribute('datetime')).toBe('2026-09-08T10:00:00Z');
  });
});
