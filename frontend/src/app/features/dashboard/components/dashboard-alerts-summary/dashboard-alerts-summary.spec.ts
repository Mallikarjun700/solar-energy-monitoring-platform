import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DashboardAlertsSummaryComponent } from './dashboard-alerts-summary';

describe('DashboardAlertsSummaryComponent', () => {
  let fixture: ComponentFixture<DashboardAlertsSummaryComponent>;
  let component: DashboardAlertsSummaryComponent;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DashboardAlertsSummaryComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(DashboardAlertsSummaryComponent);

    component = fixture.componentInstance;
  });

  it('should create', () => {
    fixture.componentRef.setInput('alerts', []);
    fixture.detectChanges();

    expect(component).toBeTruthy();
  });

  it('should calculate alert severity counts', () => {
    fixture.componentRef.setInput('alerts', [
      {
        id: 1,
        tenant_id: 'tenant-1',
        device_id: 1,
        rule_id: 1,
        alert_type: 'high_temperature',
        severity: 'critical',
        status: 'open',
        message: 'Temperature is too high.',
        triggered_at: '2026-09-05T10:00:00Z',
      },
      {
        id: 2,
        tenant_id: 'tenant-1',
        device_id: 2,
        rule_id: 2,
        alert_type: 'low_power',
        severity: 'warning',
        status: 'open',
        message: 'Power output is low.',
        triggered_at: '2026-09-05T10:05:00Z',
      },
      {
        id: 3,
        tenant_id: 'tenant-1',
        device_id: 3,
        rule_id: 3,
        alert_type: 'device_status',
        severity: 'info',
        status: 'acknowledged',
        message: 'Device status changed.',
        triggered_at: '2026-09-05T10:10:00Z',
      },
      {
        id: 4,
        tenant_id: 'tenant-1',
        device_id: 4,
        rule_id: 4,
        alert_type: 'power_drop',
        severity: 'critical',
        status: 'open',
        message: 'Power dropped unexpectedly.',
        triggered_at: '2026-09-05T10:15:00Z',
      },
    ]);

    fixture.detectChanges();

    expect(component.criticalCount()).toBe(2);
    expect(component.warningCount()).toBe(1);
    expect(component.infoCount()).toBe(1);
  });

  it('should display alert information', () => {
    fixture.componentRef.setInput('alerts', [
      {
        id: 1,
        tenant_id: 'tenant-1',
        device_id: 1,
        rule_id: 1,
        alert_type: 'high_temperature',
        severity: 'critical',
        status: 'open',
        message: 'Temperature is too high.',
        triggered_at: '2026-09-05T10:00:00Z',
      },
    ]);

    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Critical');
    expect(text).toContain('high_temperature');
    expect(text).toContain('Temperature is too high.');
    expect(text).toContain('open');
  });

  it('should display an empty state when there are no alerts', () => {
    fixture.componentRef.setInput('alerts', []);
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('No recent alerts.');
  });

  it('should handle invalid timestamps safely', () => {
    expect(component.formatTimestamp('invalid')).toBe('—');
  });
});
