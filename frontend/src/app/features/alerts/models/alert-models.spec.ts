import { Alert } from './alert.model';
import { AlertRule } from './alert-rule.model';
import { DEFAULT_ALERT_FILTERS } from './alert-filters.model';
import { AlertPaginationMeta, AlertsApiResponse } from './alert-response.model';
import { AlertDetails } from './alert-details.model';

describe('Alert models', () => {
  const alert: Alert = {
    id: 1,
    tenantId: '550e8400-e29b-41d4-a716-446655440000',
    plantId: 10,
    assetId: 20,
    deviceId: 30,
    ruleId: 40,
    eventId: '650e8400-e29b-41d4-a716-446655440000',
    alertType: 'temperature.high',
    severity: 'critical',
    status: 'open',
    message: 'Temperature exceeded the configured threshold.',
    triggeredAt: '2026-09-01T10:00:00Z',
    acknowledgedAt: null,
    resolvedAt: null,
    createdAt: '2026-09-01T10:00:00Z',
    updatedAt: '2026-09-01T10:00:00Z',
  };

  const rule: AlertRule = {
    id: 40,
    tenantId: alert.tenantId,
    name: 'High temperature',
    metric: 'temperature',
    operator: 'greater_than',
    threshold: '80.0000',
    severity: 'critical',
    alertType: 'temperature.high',
    enabled: true,
    createdAt: '2026-08-28T10:00:00Z',
    updatedAt: '2026-08-28T10:00:00Z',
  };

  it('should represent an alert', () => {
    expect(alert.id).toBe(1);
    expect(alert.status).toBe('open');
    expect(alert.severity).toBe('critical');
  });

  it('should support nullable alert relationships', () => {
    const nullableAlert: Alert = {
      ...alert,
      plantId: null,
      assetId: null,
      deviceId: null,
      eventId: null,
    };

    expect(nullableAlert.plantId).toBeNull();
    expect(nullableAlert.assetId).toBeNull();
    expect(nullableAlert.deviceId).toBeNull();
    expect(nullableAlert.eventId).toBeNull();
  });

  it('should represent an alert rule', () => {
    expect(rule.name).toBe('High temperature');
    expect(rule.metric).toBe('temperature');
    expect(rule.threshold).toBe('80.0000');
    expect(rule.enabled).toBe(true);
  });

  it('should represent alert details with a rule', () => {
    const details: AlertDetails = {
      ...alert,
      rule,
    };

    expect(details.rule).toEqual(rule);
    expect(details.rule?.metric).toBe('temperature');
  });

  it('should represent default filters', () => {
    expect(DEFAULT_ALERT_FILTERS.status).toBe('');
    expect(DEFAULT_ALERT_FILTERS.severity).toBe('');
    expect(DEFAULT_ALERT_FILTERS.alertType).toBe('');
    expect(DEFAULT_ALERT_FILTERS.deviceId).toBe('');
    expect(DEFAULT_ALERT_FILTERS.ruleId).toBe('');
    expect(DEFAULT_ALERT_FILTERS.from).toBe('');
    expect(DEFAULT_ALERT_FILTERS.to).toBe('');
    expect(DEFAULT_ALERT_FILTERS.perPage).toBe(50);
  });

  it('should represent pagination metadata', () => {
    const pagination: AlertPaginationMeta = {
      current_page: 1,
      from: 1,
      last_page: 3,
      links: [],
      path: '/api/v1/alerts',
      per_page: 50,
      to: 50,
      total: 125,
    };

    expect(pagination.current_page).toBe(1);
    expect(pagination.last_page).toBe(3);
    expect(pagination.total).toBe(125);
  });

  it('should represent a Laravel alerts API response', () => {
    const response: AlertsApiResponse = {
      current_page: 1,
      data: [],
      first_page_url: '/api/v1/alerts?page=1',
      from: null,
      last_page: 1,
      last_page_url: '/api/v1/alerts?page=1',
      links: [],
      next_page_url: null,
      path: '/api/v1/alerts',
      per_page: 50,
      prev_page_url: null,
      to: null,
      total: 0,
    };

    expect(response.data).toEqual([]);
    expect(response.current_page).toBe(1);
    expect(response.last_page).toBe(1);
    expect(response.total).toBe(0);
  });
});
