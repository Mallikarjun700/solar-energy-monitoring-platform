export type AlertStatus = 'open' | 'acknowledged' | 'resolved';

export type AlertSeverity = 'info' | 'warning' | 'critical' | 'emergency';

export interface Alert {
  id: number;
  tenantId: string;
  plantId: number | null;
  assetId: number | null;
  deviceId: number | null;
  ruleId: number;
  eventId: string | null;
  alertType: string;
  severity: AlertSeverity;
  status: AlertStatus;
  message: string;
  triggeredAt: string;
  acknowledgedAt: string | null;
  resolvedAt: string | null;
  createdAt: string;
  updatedAt: string;
}
