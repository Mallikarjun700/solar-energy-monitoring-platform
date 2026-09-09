import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { Input } from '@angular/core';

import { TenantContextService } from '../../../../core/tenant/tenant-context.service';
import { AlertDetails } from '../../models/alert-details.model';
import { AlertsService } from '../../services/alerts.service';
import { AlertDetailsComponent } from './alert-details.component';

describe('AlertDetailsComponent', () => {
  let component: AlertDetailsComponent;
  let fixture: ComponentFixture<AlertDetailsComponent>;

  const alert: AlertDetails = {
    id: 42,
    tenantId: 'tenant-1',
    plantId: 1,
    assetId: 2,
    deviceId: 10,
    ruleId: 20,
    eventId: '550e8400-e29b-41d4-a716-446655440000',
    alertType: 'temperature',
    severity: 'critical',
    status: 'open',
    message: 'Temperature exceeded threshold',
    triggeredAt: '2026-09-08T10:00:00Z',
    acknowledgedAt: null,
    resolvedAt: null,
    createdAt: '2026-09-08T10:00:00Z',
    updatedAt: '2026-09-08T10:00:00Z',
    rule: {
      id: 20,
      tenantId: 'tenant-1',
      name: 'High Temperature',
      metric: 'temperature',
      operator: 'greater_than',
      threshold: '80.0000',
      severity: 'critical',
      alertType: 'temperature',
      enabled: true,
      createdAt: '2026-09-08T09:00:00Z',
      updatedAt: '2026-09-08T09:00:00Z',
    },
  };

  const alertsServiceMock = {
    getAlert: vi.fn(),
    acknowledgeAlert: vi.fn(),
    resolveAlert: vi.fn(),
  };

  const tenantContextMock = {
    getTenantId: vi.fn(() => 'tenant-1'),
  };

  const routerMock = {
    navigate: vi.fn(),
  };

  beforeEach(async () => {
    vi.clearAllMocks();

    alertsServiceMock.getAlert.mockReturnValue(of(alert));

    await TestBed.configureTestingModule({
      imports: [AlertDetailsComponent],
      providers: [
        {
          provide: AlertsService,
          useValue: alertsServiceMock,
        },
        {
          provide: TenantContextService,
          useValue: tenantContextMock,
        },
        {
          provide: Router,
          useValue: routerMock,
        },
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: {
                get: vi.fn(() => '42'),
              },
            },
          },
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(AlertDetailsComponent);

    component = fixture.componentInstance;

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should load the alert using the route ID and tenant', () => {
    expect(alertsServiceMock.getAlert).toHaveBeenCalledWith(42, 'tenant-1');

    expect(component.alert()).toEqual(alert);
  });

  it('should render alert overview', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Alert #42');
    expect(text).toContain('temperature');
    expect(text).toContain('Temperature exceeded threshold');
    expect(text).toContain('Critical');
    expect(text).toContain('Open');
  });

  it('should render context information', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('tenant-1');
    expect(text).toContain('Device ID');
    expect(text).toContain('10');
    expect(text).toContain('Rule ID');
    expect(text).toContain('20');
    expect(text).toContain('550e8400-e29b-41d4-a716-446655440000');
  });

  it('should render rule details when the API returns a rule', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Alert Rule');
    expect(text).toContain('High Temperature');
    expect(text).toContain('greater_than');
    expect(text).toContain('80.0000');
  });

  it('should render a dash for nullable timestamps', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Acknowledged');
    expect(text).toContain('Resolved');
  });

  it('should navigate back to alerts', () => {
    component.goBack();

    expect(routerMock.navigate).toHaveBeenCalledWith(['/alerts']);
  });

  it('should show an error when loading fails', () => {
    alertsServiceMock.getAlert.mockReturnValue(throwError(() => new Error('API failure')));

    component.loadAlert();
    fixture.detectChanges();

    expect(component.error()).toBe('Unable to load the alert details. Please try again.');

    expect(fixture.nativeElement.textContent).toContain('Unable to load alert');
  });

  it('should reject an invalid route ID', async () => {
    const invalidFixture = TestBed.createComponent(AlertDetailsComponent);

    TestBed.inject(ActivatedRoute).snapshot.paramMap.get = vi.fn(() => 'invalid');

    invalidFixture.detectChanges();

    expect(invalidFixture.componentInstance.error()).toBe('Invalid alert ID.');
  });

  it('should format missing dates as an em dash', () => {
    expect(component.formatDate(null)).toBe('—');
  });

  it('should format invalid dates without throwing', () => {
    expect(component.formatDate('not-a-date')).toBe('not-a-date');
  });

  it('should allow acknowledge for an open alert', () => {
    expect(component.canAcknowledge()).toBe(true);
  });

  it('should allow resolve for an open alert', () => {
    expect(component.canResolve()).toBe(true);
  });

  it('should acknowledge an open alert', () => {
    alertsServiceMock.acknowledgeAlert.mockReturnValue(
      of({
        ...alert,
        status: 'acknowledged',
        acknowledgedAt: '2026-09-08T10:05:00Z',
      }),
    );

    component.acknowledge();
    fixture.detectChanges();

    expect(alertsServiceMock.acknowledgeAlert).toHaveBeenCalledWith(42, 'tenant-1');

    expect(component.alert()?.status).toBe('acknowledged');

    expect(component.actionSuccess()).toBe('Alert acknowledged successfully.');
  });

  it('should resolve an open alert', () => {
    alertsServiceMock.resolveAlert.mockReturnValue(
      of({
        ...alert,
        status: 'resolved',
        resolvedAt: '2026-09-08T10:10:00Z',
      }),
    );

    component.resolve();
    fixture.detectChanges();

    expect(alertsServiceMock.resolveAlert).toHaveBeenCalledWith(42, 'tenant-1');

    expect(component.alert()?.status).toBe('resolved');

    expect(component.actionSuccess()).toBe('Alert resolved successfully.');
  });

  it('should not acknowledge a non-open alert', () => {
    component.alert.set({
      ...alert,
      status: 'acknowledged',
    });

    component.acknowledge();

    expect(alertsServiceMock.acknowledgeAlert).not.toHaveBeenCalled();
  });

  it('should allow resolve for an acknowledged alert', () => {
    component.alert.set({
      ...alert,
      status: 'acknowledged',
    });

    expect(component.canResolve()).toBe(true);
  });

  it('should not allow resolve for a resolved alert', () => {
    component.alert.set({
      ...alert,
      status: 'resolved',
    });

    expect(component.canResolve()).toBe(false);
  });

  it('should prevent duplicate acknowledge actions', () => {
    component.actionLoading.set('acknowledge');

    component.acknowledge();

    expect(alertsServiceMock.acknowledgeAlert).not.toHaveBeenCalled();
  });

  it('should handle acknowledge conflict errors', () => {
    alertsServiceMock.acknowledgeAlert.mockReturnValue(throwError(() => ({ status: 409 })));

    component.acknowledge();

    expect(component.actionError()).toBe(
      'The alert state has changed. Refresh the alert and try again.',
    );

    expect(component.actionLoading()).toBeNull();
  });

  it('should handle resolve API errors', () => {
    alertsServiceMock.resolveAlert.mockReturnValue(throwError(() => ({ status: 500 })));

    component.resolve();

    expect(component.actionError()).toBe('Unable to resolve the alert.');

    expect(component.actionLoading()).toBeNull();
  });
});
