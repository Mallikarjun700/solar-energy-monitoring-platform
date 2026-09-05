export interface DashboardKpis {
  totalPlants: number;
  totalDevices: number;
  activeDevices: number;
  currentPowerKw: number | null;
  todayEnergyKwh: number | null;
}
