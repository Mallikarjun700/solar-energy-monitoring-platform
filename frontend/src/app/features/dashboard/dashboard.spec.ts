import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of, throwError, NEVER } from 'rxjs';
import { vi } from 'vitest';

import { DashboardComponent } from './dashboard';
import { DashboardService } from './services/dashboard.service';
import { DashboardViewModel } from './models/dashboard-view.model';

describe('DashboardComponent', () => {
  let fixture: ComponentFixture<DashboardComponent>;
  let component: DashboardComponent;
  let dashboardService: {
    getDashboard: ReturnType<typeof vi.fn>;
  };

  const dashboardData: DashboardViewModel = {
    kpis: {
      totalPlants: 3,
      totalDevices: 24,
      activeDevices: 21,
      currentPowerKw: 1842.5,
      todayEnergyKwh: 12456.75,
    },
    plants: [
      {
        id: 1,
        name: 'Plant Alpha',
        code: 'PLANT-001',
        location: 'Bengaluru',
        capacityKw: 2500,
        status: 'active',
        currentPowerKw: 1842.5,
        performancePercent: 73.7,
      },
    ],
    devices: [
      {
        id: 1,
        assetId: 101,
        deviceType: 'inverter',
        serialNumber: 'INV-001',
        status: 'online',
        lastSeenAt: '2026-09-05T10:00:00Z',
        currentPowerKw: 1842.5,
        temperature: 42.5,
        voltage: 415,
        current: 4.44,
        telemetryTimestamp: '2026-09-05T10:00:00Z',
      },
    ],
    energyTrend: [
      {
        timestamp: '2026-09-05T08:00:00Z',
        energyKwh: 5200,
      },
      {
        timestamp: '2026-09-05T09:00:00Z',
        energyKwh: 8400,
      },
      {
        timestamp: '2026-09-05T10:00:00Z',
        energyKwh: 12456.75,
      },
    ],
    alerts: [
      {
        id: 1,
        tenant_id: '11111111-1111-4111-8111-111111111111',
        device_id: 1,
        rule_id: 10,
        alert_type: 'high_temperature',
        severity: 'critical',
        status: 'open',
        message: 'Inverter temperature is above threshold.',
        triggered_at: '2026-09-05T09:45:00Z',
        acknowledged_at: null,
        resolved_at: null,
        created_at: '2026-09-05T09:45:00Z',
        updated_at: '2026-09-05T09:45:00Z',
      },
    ],
    recentTelemetry: [
      {
        eventId: '550e8400-e29b-41d4-a716-446655440000',
        eventType: 'telemetry.power',
        timestamp: '2026-09-05T10:00:00Z',
        powerKw: 1842.5,
        energyKwh: 12456.75,
        deviceId: 1,
      },
    ],
    recentActivity: [
      {
        id: 1,
        type: 'telemetry',
        message: 'Telemetry received from INV-001.',
        timestamp: '2026-09-05T10:00:00Z',
        deviceId: 1,
        eventId: '550e8400-e29b-41d4-a716-446655440000',
      },
    ],
  };

  beforeEach(async () => {
    dashboardService = {
      getDashboard: vi.fn(),
    };

    await TestBed.configureTestingModule({
      imports: [DashboardComponent],
      providers: [
        {
          provide: DashboardService,
          useValue: dashboardService,
        },
      ],
    }).compileComponents();
  });

  function createFixture(): void {
    fixture = TestBed.createComponent(DashboardComponent);
    component = fixture.componentInstance;
  }

  it('should create', () => {
    dashboardService.getDashboard.mockReturnValue(of(dashboardData));

    createFixture();

    expect(component).toBeTruthy();
  });

  it('should show loading state while dashboard data is loading', () => {
    dashboardService.getDashboard.mockReturnValue(NEVER);

    createFixture();
    fixture.detectChanges();

    expect(component.loading()).toBe(true);
    expect(component.dashboard()).toBeNull();
    expect(component.error()).toBeNull();

    const loadingState = fixture.nativeElement.querySelector('app-loading-state');

    expect(loadingState).toBeTruthy();
  });

  it('should load the dashboard data successfully', () => {
    dashboardService.getDashboard.mockReturnValue(of(dashboardData));

    createFixture();
    fixture.detectChanges();

    expect(dashboardService.getDashboard).toHaveBeenCalledTimes(1);
    expect(component.loading()).toBe(false);
    expect(component.error()).toBeNull();
    expect(component.dashboard()).toEqual(dashboardData);
  });

  it('should render all dashboard sections after successful loading', () => {
    dashboardService.getDashboard.mockReturnValue(of(dashboardData));

    createFixture();
    fixture.detectChanges();

    const element: HTMLElement = fixture.nativeElement;

    expect(element.textContent).toContain('Dashboard');
    expect(element.textContent).toContain('Total Plants');
    expect(element.textContent).toContain('Plant Overview');
    expect(element.textContent).toContain('Energy Trend');
    expect(element.textContent).toContain('Alerts Summary');
    expect(element.textContent).toContain('Recent Activity');
  });

  it('should pass the complete dashboard data to the rendered view', () => {
    dashboardService.getDashboard.mockReturnValue(of(dashboardData));

    createFixture();
    fixture.detectChanges();

    expect(component.dashboard()?.kpis.totalPlants).toBe(3);
    expect(component.dashboard()?.kpis.totalDevices).toBe(24);
    expect(component.dashboard()?.plants).toHaveLength(1);
    expect(component.dashboard()?.energyTrend).toHaveLength(3);
    expect(component.dashboard()?.alerts).toHaveLength(1);
    expect(component.dashboard()?.recentActivity).toHaveLength(1);
  });

  it('should show an error when dashboard loading fails', () => {
    dashboardService.getDashboard.mockReturnValue(
      throwError(() => new Error('Dashboard unavailable')),
    );

    createFixture();
    fixture.detectChanges();

    expect(dashboardService.getDashboard).toHaveBeenCalledTimes(1);
    expect(component.loading()).toBe(false);
    expect(component.dashboard()).toBeNull();
    expect(component.error()).toBe('Dashboard unavailable');

    const errorState = fixture.nativeElement.querySelector('app-error-state');

    expect(errorState).toBeTruthy();
  });

  it('should clear the previous error and reload the dashboard on retry', () => {
    dashboardService.getDashboard
      .mockReturnValueOnce(throwError(() => new Error('Dashboard unavailable')))
      .mockReturnValueOnce(of(dashboardData));

    createFixture();
    fixture.detectChanges();

    expect(component.error()).toBe('Dashboard unavailable');
    expect(dashboardService.getDashboard).toHaveBeenCalledTimes(1);

    component.retry();
    fixture.detectChanges();

    expect(dashboardService.getDashboard).toHaveBeenCalledTimes(2);
    expect(component.loading()).toBe(false);
    expect(component.error()).toBeNull();
    expect(component.dashboard()).toEqual(dashboardData);
  });

  it('should use a single dashboard aggregation request', () => {
    dashboardService.getDashboard.mockReturnValue(of(dashboardData));

    createFixture();
    fixture.detectChanges();

    expect(dashboardService.getDashboard).toHaveBeenCalledTimes(1);
  });
});
