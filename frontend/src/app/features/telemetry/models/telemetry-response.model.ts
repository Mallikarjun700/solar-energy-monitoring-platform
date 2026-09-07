import { TelemetryEvent } from './telemetry-event.model';

export interface TelemetryApiResource {
  id: number;
  event_id: string;
  tenant_id: string;
  source_id: string;
  event_type: string;
  event_timestamp: string;
  received_at: string;
  schema_version: number;
  attributes: Record<string, unknown> | null;
  payload: Record<string, unknown> | null;
  created_at: string;
}

export interface TelemetryPaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface TelemetryPaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  links: TelemetryPaginationLink[];
  path: string;
  per_page: number;
  to: number | null;
  total: number;
}

export interface TelemetryEventsApiResponse {
  current_page: number;
  data: TelemetryApiResource[];
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  links: TelemetryPaginationLink[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}

export interface TelemetryCursorLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface TelemetryCursorApiResponse {
  data: TelemetryApiResource[];
  path: string;
  per_page: number;
  next_cursor: string | null;
  next_page_url: string | null;
  prev_cursor: string | null;
  prev_page_url: string | null;
}

export interface TelemetryEventsResponse {
  events: TelemetryEvent[];
  pagination: TelemetryPaginationMeta;
}
