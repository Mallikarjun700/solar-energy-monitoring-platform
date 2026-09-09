import { Alert } from '../../models/alert.model';
import { AlertPaginationMeta } from '../../models/alert-response.model';

export interface AlertSummary {
  totalMatching: number;
  openOnPage: number;
  acknowledgedOnPage: number;
  resolvedOnPage: number;
  criticalOnPage: number;
  emergencyOnPage: number;
  pageFrom: number | null;
  pageTo: number | null;
  currentPage: number;
  lastPage: number;
}

export function buildAlertSummary(
  alerts: Alert[],
  pagination: AlertPaginationMeta | null,
): AlertSummary {
  return {
    totalMatching: pagination?.total ?? alerts.length,
    openOnPage: alerts.filter((alert) => alert.status === 'open').length,
    acknowledgedOnPage: alerts.filter((alert) => alert.status === 'acknowledged').length,
    resolvedOnPage: alerts.filter((alert) => alert.status === 'resolved').length,
    criticalOnPage: alerts.filter((alert) => alert.severity === 'critical').length,
    emergencyOnPage: alerts.filter((alert) => alert.severity === 'emergency').length,
    pageFrom: pagination?.from ?? null,
    pageTo: pagination?.to ?? null,
    currentPage: pagination?.current_page ?? 1,
    lastPage: pagination?.last_page ?? 1,
  };
}
