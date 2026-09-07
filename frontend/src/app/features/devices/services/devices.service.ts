import { Injectable, inject } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { Observable, catchError, map, throwError } from 'rxjs';

import { ApiService } from '../../../core/services/api.service';
import { ApiErrorService } from '../../../core/services/api-error.service';

import { Device } from '../models/device.model';
import {
  DeviceApiResponse,
  DeviceApiResource,
  DevicesApiResponse,
} from '../models/device-response.model';

@Injectable({
  providedIn: 'root',
})
export class DevicesService {
  private readonly api = inject(ApiService);
  private readonly apiErrorService = inject(ApiErrorService);

  getDevices(filters?: { assetId?: number; status?: string }): Observable<Device[]> {
    const params: Record<string, string | number> = {};

    if (filters?.assetId !== undefined) {
      params['asset_id'] = filters.assetId;
    }

    if (filters?.status) {
      params['status'] = filters.status;
    }

    return this.api.get<DevicesApiResponse>('/devices', params).pipe(
      map((response) => response.data.map((device) => this.mapDevice(device))),
      catchError((error: HttpErrorResponse) =>
        throwError(() => this.apiErrorService.normalize(error)),
      ),
    );
  }

  getDevice(id: number): Observable<Device> {
    return this.api.get<DeviceApiResponse>(`/devices/${id}`).pipe(
      map((response) => this.mapDevice(response.data)),
      catchError((error: HttpErrorResponse) =>
        throwError(() => this.apiErrorService.normalize(error)),
      ),
    );
  }

  private mapDevice(resource: DeviceApiResource): Device {
    return {
      id: resource.id,
      assetId: resource.asset_id,
      deviceType: resource.device_type,
      serialNumber: resource.serial_number,
      status: resource.status,
      lastSeenAt: resource.last_seen_at,
      createdAt: resource.created_at,
      updatedAt: resource.updated_at,
    };
  }
}
