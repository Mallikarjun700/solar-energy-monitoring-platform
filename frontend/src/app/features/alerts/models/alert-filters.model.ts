import { AlertSeverity, AlertStatus } from './alert.model';

export interface AlertFilters {
  status: AlertStatus | '';
  severity: AlertSeverity | '';
  alertType: string;
  deviceId: string;
  ruleId: string;
  from: string;
  to: string;
  perPage: number;
}

export const DEFAULT_ALERT_FILTERS: AlertFilters = {
  status: '',
  severity: '',
  alertType: '',
  deviceId: '',
  ruleId: '',
  from: '',
  to: '',
  perPage: 50,
};
