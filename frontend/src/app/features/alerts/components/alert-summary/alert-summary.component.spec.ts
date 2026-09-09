import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AlertSummaryComponent } from './alert-summary.component';
import { Alert } from '../../models/alert.model';
import { AlertPaginationMeta } from '../../models/alert-response.model';

describe('AlertSummaryComponent', () => {
  let component: AlertSummaryComponent;
  let fixture: ComponentFixture<AlertSummaryComponent>;

  const alerts: Alert[] = [
    {
      id: 1,
      tenantId: 'tenant-1',
      plantId: 1,
      assetId: 1,
      deviceId: 1,
      ruleId: 1,
      eventId: null,
      alertType: 'temperature',
      severity: 'critical',
      status: 'open',
      message: 'High temperature',
      triggeredAt: '2026-09-08T10:00:00Z',
      acknowledgedAt: null,
      resolvedAt: null,
      createdAt: '2026-09-08T10:00:00Z',
      updatedAt: '2026-09-08T10:00:00Z',
    },
    {
      id: 2,
      tenantId: 'tenant-1',
      plantId: 1,
      assetId: 1,
      deviceId: 2,
      ruleId: 2,
      eventId: null,
      alertType: 'voltage',
      severity: 'emergency',
      status: 'acknowledged',
      message: 'Voltage emergency',
      triggeredAt: '2026-09-08T10:01:00Z',
      acknowledgedAt: '2026-09-08T10:02:00Z',
      resolvedAt: null,
      createdAt: '2026-09-08T10:01:00Z',
      updatedAt: '2026-09-08T10:02:00Z',
    },
    {
      id: 3,
      tenantId: 'tenant-1',
      plantId: 1,
      assetId: 1,
      deviceId: 3,
      ruleId: 3,
      eventId: null,
      alertType: 'power',
      severity: 'warning',
      status: 'resolved',
      message: 'Power warning resolved',
      triggeredAt: '2026-09-08T10:03:00Z',
      acknowledgedAt: null,
      resolvedAt: '2026-09-08T10:04:00Z',
      createdAt: '2026-09-08T10:03:00Z',
      updatedAt: '2026-09-08T10:04:00Z',
    },
    {
      id: 4,
      tenantId: 'tenant-1',
      plantId: 1,
      assetId: 1,
      deviceId: 4,
      ruleId: 4,
      eventId: null,
      alertType: 'temperature',
      severity: 'critical',
      status: 'open',
      message: 'Critical temperature',
      triggeredAt: '2026-09-08T10:05:00Z',
      acknowledgedAt: null,
      resolvedAt: null,
      createdAt: '2026-09-08T10:05:00Z',
      updatedAt: '2026-09-08T10:05:00Z',
    },
  ];

  const pagination: AlertPaginationMeta = {
    current_page: 1,
    from: 1,
    last_page: 3,
    links: [],
    path: '/api/v1/alerts',
    per_page: 4,
    to: 4,
    total: 12,
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AlertSummaryComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(AlertSummaryComponent);

    component = fixture.componentInstance;

    fixture.componentRef.setInput('alerts', alerts);
    fixture.componentRef.setInput('pagination', pagination);

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should calculate total matching alerts from pagination', () => {
    expect(component.summary().totalMatching).toBe(12);
  });

  it('should calculate status counts for the current page', () => {
    expect(component.summary().openOnPage).toBe(2);
    expect(component.summary().acknowledgedOnPage).toBe(1);
    expect(component.summary().resolvedOnPage).toBe(1);
  });

  it('should calculate severity counts for the current page', () => {
    expect(component.summary().criticalOnPage).toBe(2);
    expect(component.summary().emergencyOnPage).toBe(1);
  });

  it('should expose pagination range', () => {
    expect(component.summary().pageFrom).toBe(1);
    expect(component.summary().pageTo).toBe(4);
    expect(component.summary().currentPage).toBe(1);
    expect(component.summary().lastPage).toBe(3);
  });

  it('should render summary values', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('12');
    expect(text).toContain('Showing');
    expect(text).toContain('1–4');
    expect(text).toContain('of 12 matching alerts.');
  });

  it('should handle empty pagination', () => {
    fixture.componentRef.setInput('alerts', []);

    fixture.componentRef.setInput('pagination', null);

    fixture.detectChanges();

    expect(component.summary()).toEqual({
      totalMatching: 0,
      openOnPage: 0,
      acknowledgedOnPage: 0,
      resolvedOnPage: 0,
      criticalOnPage: 0,
      emergencyOnPage: 0,
      pageFrom: null,
      pageTo: null,
      currentPage: 1,
      lastPage: 1,
    });
  });

  it('should not render a result range when there are no matching alerts', () => {
    fixture.componentRef.setInput('alerts', []);

    fixture.componentRef.setInput('pagination', {
      ...pagination,
      from: null,
      to: null,
      total: 0,
    });

    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('.result-range')).toBeNull();
  });
});
