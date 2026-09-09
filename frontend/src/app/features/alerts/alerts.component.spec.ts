import { ComponentFixture, TestBed } from '@angular/core/testing';
import { NEVER, of, throwError } from 'rxjs';

import { AlertsComponent } from './alerts.component';
import { AlertsService } from './services/alerts.service';
import { TenantContextService } from '../../core/tenant/tenant-context.service';
import { Alert } from './models/alert.model';

describe('AlertsComponent', () => {
  let component: AlertsComponent;
  let fixture: ComponentFixture<AlertsComponent>;

  let alertsService: {
    getAlerts: ReturnType<typeof vi.fn>;
  };

  let tenantContext: {
    getTenantId: ReturnType<typeof vi.fn>;
  };

  const tenantId = '550e8400-e29b-41d4-a716-446655440000';

  const alert: Alert = {
    id: 1,
    tenantId,
    plantId: 10,
    assetId: 20,
    deviceId: 30,
    ruleId: 40,
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
  };

  const response = {
    alerts: [alert],
    pagination: {
      current_page: 1,
      from: 1,
      last_page: 1,
      links: [],
      path: '/api/v1/alerts',
      per_page: 50,
      to: 1,
      total: 1,
    },
  };

  beforeEach(async () => {
    alertsService = {
      getAlerts: vi.fn().mockReturnValue(of(response)),
    };

    tenantContext = {
      getTenantId: vi.fn().mockReturnValue(tenantId),
    };

    await TestBed.configureTestingModule({
      imports: [AlertsComponent],
      providers: [
        {
          provide: AlertsService,
          useValue: alertsService,
        },
        {
          provide: TenantContextService,
          useValue: tenantContext,
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(AlertsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should load alerts on initialization', () => {
    expect(tenantContext.getTenantId).toHaveBeenCalled();
    expect(alertsService.getAlerts).toHaveBeenCalledTimes(1);
    expect(alertsService.getAlerts).toHaveBeenCalledWith(component.filters(), tenantId);
  });

  it('should render the page heading', () => {
    const heading = fixture.nativeElement.querySelector('h1');

    expect(heading?.textContent).toContain('Alerts');
  });

  it('should render loaded alert count', () => {
    const summary = fixture.nativeElement.querySelector('app-alert-summary');

    expect(summary?.textContent).toContain('1');
  });

  it('should render pagination information', () => {
    const summary = fixture.nativeElement.querySelector('app-alert-summary');

    expect(summary?.textContent).toContain('Showing');
    expect(summary?.textContent).toContain('1–1');
    expect(summary?.textContent).toContain('1');
  });

  it('should show empty state when no alerts are returned', () => {
    alertsService.getAlerts.mockReturnValue(
      of({
        alerts: [],
        pagination: {
          current_page: 1,
          from: null,
          last_page: 1,
          links: [],
          path: '/api/v1/alerts',
          per_page: 50,
          to: null,
          total: 0,
        },
      }),
    );

    component.loadAlerts();
    fixture.detectChanges();

    const emptyState = fixture.nativeElement.querySelector('.empty-state');

    expect(emptyState?.textContent).toContain('No alerts found');
  });

  it('should show an error state when loading fails', () => {
    alertsService.getAlerts.mockReturnValue(
      throwError(() => ({
        message: 'Unable to reach the server.',
      })),
    );

    component.loadAlerts();
    fixture.detectChanges();

    const state = fixture.nativeElement.querySelector('.error-state');

    expect(state?.textContent).toContain('Unable to load alerts');
  });

  it('should retry loading alerts', () => {
    component.retry();

    expect(alertsService.getAlerts).toHaveBeenCalledTimes(2);
  });

  it('should refresh alerts', () => {
    component.refresh();

    expect(alertsService.getAlerts).toHaveBeenCalledTimes(2);
  });

  it('should update filters and reload alerts', () => {
    const filters = {
      status: 'open' as const,
      severity: 'critical' as const,
      alertType: '',
      deviceId: '',
      ruleId: '',
      from: '',
      to: '',
      perPage: 25,
    };

    component.updateFilters(filters);

    expect(component.filters()).toEqual(filters);
    expect(alertsService.getAlerts).toHaveBeenCalledTimes(2);
    expect(alertsService.getAlerts).toHaveBeenLastCalledWith(filters, tenantId);
  });

  it('should clear filters and reload alerts', () => {
    component.clearFilters();

    expect(component.filters()).toEqual({
      status: '',
      severity: '',
      alertType: '',
      deviceId: '',
      ruleId: '',
      from: '',
      to: '',
      perPage: 50,
    });

    expect(alertsService.getAlerts).toHaveBeenCalledTimes(2);
  });

  it('should show loading state while alerts are loading', () => {
    alertsService.getAlerts.mockReturnValue(NEVER);

    const fixture = TestBed.createComponent(AlertsComponent);

    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Loading alerts');
  });

  it('should show empty state when the API returns no alerts', () => {
    alertsService.getAlerts.mockReturnValue(
      of({
        alerts: [],
        pagination: {
          currentPage: 1,
          lastPage: 1,
          perPage: 50,
          total: 0,
          from: null,
          to: null,
        },
      }),
    );

    component.loadAlerts();
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('No alerts found');

    expect(fixture.nativeElement.textContent).toContain(
      'There are no alerts matching the current filters.',
    );
  });

  it('should show error state when the API fails', () => {
    alertsService.getAlerts.mockReturnValue(throwError(() => new Error('API failure')));

    component.loadAlerts();
    fixture.detectChanges();

    expect(component.error()).toBe('Unable to load alerts. Please try again.');

    expect(fixture.nativeElement.textContent).toContain('Unable to load alerts');

    expect(fixture.nativeElement.textContent).toContain('Retry');
  });

  it('should clear stale alerts when loading fails', () => {
    component.alerts.set([
      {
        ...alert,
        id: 1,
      },
    ]);

    alertsService.getAlerts.mockReturnValue(throwError(() => new Error('API failure')));

    component.loadAlerts();

    expect(component.alerts()).toEqual([]);
    expect(component.pagination()).toBeNull();
  });

  it('should retry after an API failure', () => {
    alertsService.getAlerts
      .mockReturnValueOnce(throwError(() => new Error('API failure')))
      .mockReturnValueOnce(
        of({
          alerts: [alert],
          pagination: response.pagination,
        }),
      );

    component.loadAlerts();

    expect(component.error()).not.toBeNull();

    component.retry();

    expect(component.error()).toBeNull();
    expect(component.alerts()).toEqual([alert]);
  });

  it('should disable refresh while loading', () => {
    alertsService.getAlerts.mockReturnValue(NEVER);

    const fixture = TestBed.createComponent(AlertsComponent);

    fixture.detectChanges();

    const button = fixture.nativeElement.querySelector('.refresh-button') as HTMLButtonElement;

    expect(button.disabled).toBe(true);
  });

  it('should render alert summary and list when data exists', () => {
    alertsService.getAlerts.mockReturnValue(
      of({
        alerts: [alert],
        pagination: response.pagination,
      }),
    );

    component.loadAlerts();
    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('app-alert-summary')).toBeTruthy();

    expect(fixture.nativeElement.querySelector('app-alert-list')).toBeTruthy();
  });
});
