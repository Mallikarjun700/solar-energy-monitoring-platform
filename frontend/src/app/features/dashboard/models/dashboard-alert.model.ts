export type AlertStatus = 'open' | 'acknowledged' | 'resolved';

export type AlertSeverity = 'info' | 'warning' | 'critical' | 'emergency';

export interface DashboardAlert {
  id: number;
  tenant_id: string;
  device_id: number | null;
  rule_id: number | null;
  alert_type: string;
  severity: AlertSeverity;
  status: AlertStatus;
  message?: string | null;
  triggered_at: string;
  acknowledged_at?: string | null;
  resolved_at?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface PaginatedAlerts {
  current_page: number;
  data: DashboardAlert[];
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}
