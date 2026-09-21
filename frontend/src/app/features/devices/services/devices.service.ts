import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { ApiService } from '../../../core/services/api.service';
import {
  CreateDeviceRequest,
  Device,
  DeviceStatus,
  UpdateDeviceRequest,
} from '../models/device.model';

interface DeviceApiResource {
  id: number;
  asset_id: number;
  device_type: string;
  serial_number: string;
  status: DeviceStatus | null;
  last_seen_at: string | null;
  created_at: string;
  updated_at: string;
}

interface DeviceListResponse {
  data: DeviceApiResource[];
}

interface DeviceResponse {
  message?: string;
  data: DeviceApiResource;
}

@Injectable({
  providedIn: 'root',
})
export class DevicesService {
  private readonly api = inject(ApiService);

  getDevices(filters?: {
    assetId?: number;
    status?: DeviceStatus;
  }): Observable<Device[]> {
    return this.api
      .get<DeviceListResponse>('/devices', {
        ...(filters?.assetId !== undefined
          ? { asset_id: filters.assetId }
          : {}),
        ...(filters?.status ? { status: filters.status } : {}),
      })
      .pipe(map((response) => response.data.map(this.mapDevice)));
  }

  getDevice(id: number): Observable<Device> {
    return this.api
      .get<DeviceApiResource>(`/devices/${id}`)
      .pipe(map((response) => this.mapDevice(response)));
  }

  createDevice(payload: CreateDeviceRequest): Observable<Device> {
    return this.api
      .post<DeviceResponse>('/devices', payload)
      .pipe(map((response) => this.mapDevice(response.data)));
  }

  updateDevice(
    id: number,
    payload: UpdateDeviceRequest,
  ): Observable<Device> {
    return this.api
      .put<DeviceResponse>(`/devices/${id}`, payload)
      .pipe(map((response) => this.mapDevice(response.data)));
  }

  deleteDevice(id: number): Observable<void> {
    return this.api.delete<void>(`/devices/${id}`);
  }

  private mapDevice(resource: DeviceApiResource): Device {
    return {
      id: resource.id,
      assetId: resource.asset_id,
      deviceType: resource.device_type,
      serialNumber: resource.serial_number,
      status: resource.status ?? '',
      lastSeenAt: resource.last_seen_at,
      createdAt: resource.created_at,
      updatedAt: resource.updated_at,
    };
  }
}