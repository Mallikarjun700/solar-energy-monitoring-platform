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

export interface CreateDeviceRequest {
  asset_id: number;
  device_type: string;
  serial_number: string;
  status?: string | null;
  last_seen_at?: string | null;
}

export type UpdateDeviceRequest = Partial<CreateDeviceRequest>;
