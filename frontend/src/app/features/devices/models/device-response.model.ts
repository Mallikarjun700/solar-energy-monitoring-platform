export interface DeviceApiResource {
  id: number;
  asset_id: number;
  device_type: string;
  serial_number: string;
  status: string;
  last_seen_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface DevicesApiResponse {
  status: string;
  message: string;
  data: DeviceApiResource[];
  correlation_id?: string;
}

export interface DeviceApiResponse {
  status: string;
  message: string;
  data: DeviceApiResource;
  correlation_id?: string;
}
