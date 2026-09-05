import { DashboardDevice } from './dashboard-device.model';
import { DashboardAlert } from './dashboard-alert.model';
import { DashboardKpis } from './dashboard-kpi.model';
import { DashboardPlant } from './dashboard-plant.model';
import { DashboardTelemetrySummary } from './dashboard-telemetry.model';
import { DashboardActivity } from './dashboard-activity.model';

export interface DashboardEnergyPoint {
  timestamp: string;
  energyKwh: number | null;
}

export interface DashboardViewModel {
  kpis: DashboardKpis;
  plants: DashboardPlant[];
  devices: DashboardDevice[];
  energyTrend: DashboardEnergyPoint[];
  alerts: DashboardAlert[];
  recentTelemetry: DashboardTelemetrySummary[];
  recentActivity: DashboardActivity[];
}
