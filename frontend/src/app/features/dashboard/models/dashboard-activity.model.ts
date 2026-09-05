export type DashboardActivityType = 'telemetry' | 'alert' | 'device' | 'plant' | 'system' | string;

export interface DashboardActivity {
  id: string | number;
  type: DashboardActivityType;
  message: string;
  timestamp: string;
  deviceId?: number | null;
  eventId?: string | null;
}
