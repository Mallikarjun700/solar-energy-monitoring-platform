export type DashboardDeviceStatus =
  'online' | 'offline' | 'active' | 'inactive' | 'maintenance' | string;

export interface DashboardDevice {
  id: number;
  assetId: number | null;
  deviceType: string;
  serialNumber: string;
  status: DashboardDeviceStatus;
  lastSeenAt: string | null;

  currentPowerKw: number | null;
  temperature: number | null;
  voltage: number | null;
  current: number | null;

  telemetryTimestamp: string | null;
}
