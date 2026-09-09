import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { ApiService } from '../../../core/services/api.service';
import { Alert, AlertSeverity, AlertStatus } from '../models/alert.model';
import {
  AlertApiResource,
  AlertPaginationMeta,
  AlertsApiResponse,
  AlertsResponse,
} from '../models/alert-response.model';
import { AlertDetails } from '../models/alert-details.model';
import { AlertFilters } from '../models/alert-filters.model';
import { AlertRule } from '../models/alert-rule.model';

type AlertApiDetailsResource = AlertApiResource & {
  rule?: AlertRuleApiResource | null;
};

interface AlertRuleApiResource {
  id: number;
  tenant_id: string;
  name: string;
  metric: string;
  operator: AlertRule['operator'];
  threshold: string;
  severity: string;
  alert_type: string;
  enabled: boolean;
  created_at: string;
  updated_at: string;
}

@Injectable({
  providedIn: 'root',
})
export class AlertsService {
  private readonly api = inject(ApiService);

  private readonly alertsEndpoint = '/alerts';

  getAlerts(filters: AlertFilters, tenantId: string): Observable<AlertsResponse> {
    const params: Record<string, string | number> = {
      tenant_id: tenantId,
      per_page: filters.perPage,
    };

    if (filters.status) {
      params['status'] = filters.status;
    }

    if (filters.severity) {
      params['severity'] = filters.severity;
    }

    const alertType = filters.alertType.trim();

    if (alertType) {
      params['alert_type'] = alertType;
    }

    const deviceId = filters.deviceId.trim();

    if (deviceId) {
      params['device_id'] = deviceId;
    }

    const ruleId = filters.ruleId.trim();

    if (ruleId) {
      params['rule_id'] = ruleId;
    }

    if (filters.from) {
      params['from'] = filters.from;
    }

    if (filters.to) {
      params['to'] = filters.to;
    }

    return this.api.get<AlertsApiResponse>(this.alertsEndpoint, params).pipe(
      map((response) => ({
        alerts: response.data.map((resource) => this.mapAlert(resource)),
        pagination: this.mapPagination(response),
      })),
    );
  }

  getAlert(id: number, tenantId: string): Observable<AlertDetails> {
    return this.api
      .get<AlertApiDetailsResource>(`${this.alertsEndpoint}/${id}`, {
        tenant_id: tenantId,
      })
      .pipe(
        map((resource) => ({
          ...this.mapAlert(resource),
          rule: resource.rule ? this.mapRule(resource.rule) : null,
        })),
      );
  }

  acknowledgeAlert(id: number, tenantId: string): Observable<Alert> {
    return this.api
      .post<AlertApiResource>(
        this.withTenantQuery(`${this.alertsEndpoint}/${id}/acknowledge`, tenantId),
        {},
      )
      .pipe(map((resource) => this.mapAlert(resource)));
  }

  resolveAlert(id: number, tenantId: string): Observable<Alert> {
    return this.api
      .post<AlertApiResource>(
        this.withTenantQuery(`${this.alertsEndpoint}/${id}/resolve`, tenantId),
        {},
      )
      .pipe(map((resource) => this.mapAlert(resource)));
  }

  private withTenantQuery(path: string, tenantId: string): string {
    return `${path}?tenant_id=${encodeURIComponent(tenantId)}`;
  }

  private mapAlert(resource: AlertApiResource): Alert {
    return {
      id: resource.id,
      tenantId: resource.tenant_id,
      plantId: resource.plant_id,
      assetId: resource.asset_id,
      deviceId: resource.device_id,
      ruleId: resource.rule_id,
      eventId: resource.event_id,
      alertType: resource.alert_type,
      severity: resource.severity as AlertSeverity,
      status: resource.status as AlertStatus,
      message: resource.message,
      triggeredAt: resource.triggered_at,
      acknowledgedAt: resource.acknowledged_at,
      resolvedAt: resource.resolved_at,
      createdAt: resource.created_at,
      updatedAt: resource.updated_at,
    };
  }

  private mapRule(resource: AlertRuleApiResource): AlertRule {
    return {
      id: resource.id,
      tenantId: resource.tenant_id,
      name: resource.name,
      metric: resource.metric,
      operator: resource.operator,
      threshold: resource.threshold,
      severity: resource.severity as AlertSeverity,
      alertType: resource.alert_type,
      enabled: resource.enabled,
      createdAt: resource.created_at,
      updatedAt: resource.updated_at,
    };
  }

  private mapPagination(response: AlertsApiResponse): AlertPaginationMeta {
    return {
      current_page: response.current_page,
      from: response.from,
      last_page: response.last_page,
      links: response.links,
      path: response.path,
      per_page: response.per_page,
      to: response.to,
      total: response.total,
    };
  }
}
