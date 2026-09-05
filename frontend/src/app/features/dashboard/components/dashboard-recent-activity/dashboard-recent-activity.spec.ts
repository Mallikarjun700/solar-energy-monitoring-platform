import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DashboardRecentActivityComponent } from './dashboard-recent-activity';

describe('DashboardRecentActivityComponent', () => {
  let fixture: ComponentFixture<DashboardRecentActivityComponent>;
  let component: DashboardRecentActivityComponent;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DashboardRecentActivityComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(DashboardRecentActivityComponent);

    component = fixture.componentInstance;
  });

  it('should create', () => {
    fixture.componentRef.setInput('activities', []);
    fixture.detectChanges();

    expect(component).toBeTruthy();
  });

  it('should display recent activity', () => {
    fixture.componentRef.setInput('activities', [
      {
        id: 1,
        type: 'telemetry',
        message: 'Power telemetry received.',
        timestamp: '2026-09-05T10:00:00Z',
        deviceId: 101,
        eventId: 'event-1',
      },
      {
        id: 2,
        type: 'alert',
        message: 'High temperature alert triggered.',
        timestamp: '2026-09-05T10:05:00Z',
        deviceId: 102,
        eventId: null,
      },
    ]);

    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;

    expect(text).toContain('telemetry');
    expect(text).toContain('Power telemetry received.');
    expect(text).toContain('Device 101');
    expect(text).toContain('alert');
    expect(text).toContain('High temperature alert triggered.');
    expect(text).toContain('Device 102');
  });

  it('should display an empty state when there is no activity', () => {
    fixture.componentRef.setInput('activities', []);
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('No recent activity.');
  });

  it('should handle invalid timestamps safely', () => {
    expect(component.formatTimestamp('invalid')).toBe('—');
  });
});
