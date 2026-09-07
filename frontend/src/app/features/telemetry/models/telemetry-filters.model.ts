export interface TelemetryFilters {
  tenantId: string;
  sourceId: string;
  eventType: string;
  from: string;
  to: string;
  perPage: number;
}

export const DEFAULT_TELEMETRY_FILTERS: TelemetryFilters = {
  tenantId: '',
  sourceId: '',
  eventType: '',
  from: '',
  to: '',
  perPage: 50,
};
