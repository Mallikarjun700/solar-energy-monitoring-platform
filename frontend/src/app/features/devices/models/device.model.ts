export type DeviceStatus = string;

export interface Device {
  id: number;
  assetId: number;
  deviceType: string;
  serialNumber: string;
  status: DeviceStatus;
  lastSeenAt: string | null;
  createdAt: string;
  updatedAt: string;
}
