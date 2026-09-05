export type PlantStatus = string;

export interface DashboardPlant {
  id: number;
  name: string;
  code: string;
  location: string | null;
  capacityKw: number | null;
  status: PlantStatus;
  currentPowerKw: number | null;
  performancePercent: number | null;
}
