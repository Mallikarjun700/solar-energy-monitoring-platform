import { TestBed } from '@angular/core/testing';
import { of, Observable, Subscriber } from 'rxjs';

import { ApiService } from '../../../core/services/api.service';
import { AlertsService } from './alerts.service';
import { AlertFilters } from '../models/alert-filters.model';

describe('AlertsService', () => {
  let service: AlertsService;
  let api: {
    get: ReturnType<typeof vi.fn>;
    post: ReturnType<typeof vi.fn>;
  };

  const tenantId = '550e8400-e29b-41d4-a716-446655440000';

  const alertResource = {
    id: 101,
    tenant_id: tenantId,
    plant_id: 1,
    asset_id: 2,
    device_id: 10,
    rule_id: 5,
    event_id: '650e8400-e29b-41d4-a716-446655440000',
    alert_type: 'temperature',
    severity: 'critical',
    status: 'open',
    message: 'Temperature exceeded threshold',
    triggered_at: '2026-09-08T10:00:00Z',
    acknowledged_at: null,
    resolved_at: null,
    created_at: '2026-09-08T10:00:00Z',
    updated_at: '2026-09-08T10:00:00Z',
  };

  const paginationResponse = {
    current_page: 1,
    data: [alertResource],
    first_page_url: '/api/v1/alerts?page=1',
    from: 1,
    last_page: 1,
    last_page_url: '/api/v1/alerts?page=1',
    links: [],
    next_page_url: null,
    path: '/api/v1/alerts',
    per_page: 50,
    prev_page_url: null,
    to: 1,
    total: 1,
  };

  beforeEach(() => {
    api = {
      get: vi.fn(),
      post: vi.fn(),
    };

    TestBed.configureTestingModule({
      providers: [
        AlertsService,
        {
          provide: ApiService,
          useValue: api,
        },
      ],
    });

    service = TestBed.inject(AlertsService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should request alerts with tenant and active filters', () => {
    api.get.mockReturnValue(of(paginationResponse));

    const filters: AlertFilters = {
      status: 'open',
      severity: 'critical',
      alertType: ' temperature ',
      deviceId: '10',
      ruleId: '5',
      from: '2026-09-01',
      to: '2026-09-08',
      perPage: 25,
    };

    service.getAlerts(filters, tenantId).subscribe();

    expect(api.get).toHaveBeenCalledWith('/alerts', {
      tenant_id: tenantId,
      status: 'open',
      severity: 'critical',
      alert_type: 'temperature',
      device_id: '10',
      rule_id: '5',
      from: '2026-09-01',
      to: '2026-09-08',
      per_page: 25,
    });
  });

  it('should omit empty filters', () => {
    api.get.mockReturnValue(of(paginationResponse));

    const filters: AlertFilters = {
      status: '',
      severity: '',
      alertType: '   ',
      deviceId: '',
      ruleId: '',
      from: '',
      to: '',
      perPage: 50,
    };

    service.getAlerts(filters, tenantId).subscribe();

    expect(api.get).toHaveBeenCalledWith('/alerts', {
      tenant_id: tenantId,
      per_page: 50,
    });
  });

  it('should map paginated alerts from snake_case to camelCase', () => {
    api.get.mockReturnValue(of(paginationResponse));

    service
      .getAlerts(
        {
          status: '',
          severity: '',
          alertType: '',
          deviceId: '',
          ruleId: '',
          from: '',
          to: '',
          perPage: 50,
        },
        tenantId,
      )
      .subscribe((response) => {
        expect(response.alerts).toHaveLength(1);
        expect(response.alerts[0]).toEqual({
          id: 101,
          tenantId,
          plantId: 1,
          assetId: 2,
          deviceId: 10,
          ruleId: 5,
          eventId: '650e8400-e29b-41d4-a716-446655440000',
          alertType: 'temperature',
          severity: 'critical',
          status: 'open',
          message: 'Temperature exceeded threshold',
          triggeredAt: '2026-09-08T10:00:00Z',
          acknowledgedAt: null,
          resolvedAt: null,
          createdAt: '2026-09-08T10:00:00Z',
          updatedAt: '2026-09-08T10:00:00Z',
        });

        expect(response.pagination.current_page).toBe(1);
        expect(response.pagination.total).toBe(1);
      });
  });

  it('should get alert details with tenant query parameter', () => {
    api.get.mockReturnValue(
      of({
        ...alertResource,
        rule: {
          id: 5,
          tenant_id: tenantId,
          name: 'High Temperature',
          metric: 'temperature',
          operator: 'greater_than',
          threshold: '80.0000',
          severity: 'critical',
          alert_type: 'temperature',
          enabled: true,
          created_at: '2026-09-01T00:00:00Z',
          updated_at: '2026-09-01T00:00:00Z',
        },
      }),
    );

    service.getAlert(101, tenantId).subscribe((alert) => {
      expect(alert.id).toBe(101);
      expect(alert.rule).toEqual({
        id: 5,
        tenantId,
        name: 'High Temperature',
        metric: 'temperature',
        operator: 'greater_than',
        threshold: '80.0000',
        severity: 'critical',
        alertType: 'temperature',
        enabled: true,
        createdAt: '2026-09-01T00:00:00Z',
        updatedAt: '2026-09-01T00:00:00Z',
      });
    });

    expect(api.get).toHaveBeenCalledWith('/alerts/101', {
      tenant_id: tenantId,
    });
  });

  it('should handle an alert without a rule', () => {
    api.get.mockReturnValue(
      of({
        ...alertResource,
        rule: null,
      }),
    );

    service.getAlert(101, tenantId).subscribe((alert) => {
      expect(alert.rule).toBeNull();
    });
  });

  it('should acknowledge an alert using tenant query parameter', () => {
    api.post.mockReturnValue(
      of({
        ...alertResource,
        status: 'acknowledged',
        acknowledged_at: '2026-09-08T11:00:00Z',
      }),
    );

    service.acknowledgeAlert(101, tenantId).subscribe((alert) => {
      expect(alert.status).toBe('acknowledged');
      expect(alert.acknowledgedAt).toBe('2026-09-08T11:00:00Z');
    });

    expect(api.post).toHaveBeenCalledWith(`/alerts/101/acknowledge?tenant_id=${tenantId}`, {});
  });

  it('should resolve an alert using tenant query parameter', () => {
    api.post.mockReturnValue(
      of({
        ...alertResource,
        status: 'resolved',
        resolved_at: '2026-09-08T12:00:00Z',
      }),
    );

    service.resolveAlert(101, tenantId).subscribe((alert) => {
      expect(alert.status).toBe('resolved');
      expect(alert.resolvedAt).toBe('2026-09-08T12:00:00Z');
    });

    expect(api.post).toHaveBeenCalledWith(`/alerts/101/resolve?tenant_id=${tenantId}`, {});
  });

  it('should URL encode the tenant ID for action requests', () => {
    api.post.mockReturnValue(of(alertResource));

    const specialTenantId = 'tenant/value?test=true';

    service.acknowledgeAlert(101, specialTenantId).subscribe();

    expect(api.post).toHaveBeenCalledWith(
      '/alerts/101/acknowledge?tenant_id=tenant%2Fvalue%3Ftest%3Dtrue',
      {},
    );
  });

  it('should propagate API errors', () => {
    const error = new Error('API failure');

    api.get.mockReturnValue(
      new Observable((subscriber: Subscriber<any>) => {
        subscriber.error(error);
      }),
    );

    service
      .getAlerts(
        {
          status: '',
          severity: '',
          alertType: '',
          deviceId: '',
          ruleId: '',
          from: '',
          to: '',
          perPage: 50,
        },
        tenantId,
      )
      .subscribe({
        next: () => fail('Expected an error'),
        error: (received) => {
          expect(received).toBe(error);
        },
      });
  });

  function fail(arg0: string): void {
    throw new Error('Function not implemented.');
  }
});
