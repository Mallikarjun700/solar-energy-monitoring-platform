import { AlertSeverity } from './alert.model';

export type AlertOperator =
  | 'greater_than'
  | 'greater_than_or_equal'
  | 'less_than'
  | 'less_than_or_equal'
  | 'equal'
  | 'not_equal';

export interface AlertRule {
  id: number;
  tenantId: string;
  name: string;
  metric: string;
  operator: AlertOperator;
  threshold: string;
  severity: AlertSeverity;
  alertType: string;
  enabled: boolean;
  createdAt: string;
  updatedAt: string;
}
