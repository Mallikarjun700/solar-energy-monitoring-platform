import { Alert } from './alert.model';
import { AlertRule } from './alert-rule.model';

export interface AlertDetails extends Alert {
  rule: AlertRule | null;
}
