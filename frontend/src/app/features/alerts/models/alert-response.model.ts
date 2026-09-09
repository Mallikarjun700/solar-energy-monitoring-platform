import { Alert } from './alert.model';

export interface AlertApiResource {
  id: number;
  tenant_id: string;
  plant_id: number | null;
  asset_id: number | null;
  device_id: number | null;
  rule_id: number;
  event_id: string | null;
  alert_type: string;
  severity: string;
  status: string;
  message: string;
  triggered_at: string;
  acknowledged_at: string | null;
  resolved_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface AlertPaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface AlertPaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  links: AlertPaginationLink[];
  path: string;
  per_page: number;
  to: number | null;
  total: number;
}

export interface AlertsApiResponse {
  current_page: number;
  data: AlertApiResource[];
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  links: AlertPaginationLink[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}

export interface AlertsResponse {
  alerts: Alert[];
  pagination: AlertPaginationMeta;
}
