export interface DashboardTelemetryPayload {
  power?: number;
  power_kw?: number;
  energy_generated?: number;
  temperature?: number;
  voltage?: number;
  current?: number;
  device_id?: number | string;
  [key: string]: unknown;
}

export interface DashboardLatestTelemetry {
  eventId: string;
  eventType: string;
  timestamp: string;
  attributes: DashboardTelemetryPayload | null;
  payload: DashboardTelemetryPayload | null;
}

export interface DashboardTelemetrySummary {
  eventId: string;
  eventType: string;
  timestamp: string;
  powerKw: number | null;
  energyKwh: number | null;
  deviceId: number | string | null;
}
