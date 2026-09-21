import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { ApiService } from '../../../core/services/api.service';
import {
  Asset,
  AssetStatus,
  CreateAssetRequest,
  UpdateAssetRequest,
} from '../models/asset.model';

interface AssetApiResource {
  id: number;
  plant_id: number;
  name: string;
  asset_type: string;
  serial_number: string | null;
  status: AssetStatus;
  location: string | null;
  created_at: string;
  updated_at: string;
}

interface AssetListResponse {
  data: AssetApiResource[];
}

interface AssetResponse {
  message?: string;
  data: AssetApiResource;
}

@Injectable({
  providedIn: 'root',
})
export class AssetsService {
  private readonly api = inject(ApiService);

  getAssets(filters?: {
    plantId?: number;
    status?: AssetStatus;
  }): Observable<Asset[]> {
    return this.api
      .get<AssetListResponse>('/assets', {
        ...(filters?.plantId !== undefined
          ? { plant_id: filters.plantId }
          : {}),
        ...(filters?.status ? { status: filters.status } : {}),
      })
      .pipe(map((response) => response.data.map(this.mapAsset)));
  }

  getAsset(id: number): Observable<Asset> {
    return this.api
      .get<AssetApiResource>(`/assets/${id}`)
      .pipe(map((response) => this.mapAsset(response)));
  }

  createAsset(payload: CreateAssetRequest): Observable<Asset> {
    return this.api
      .post<AssetResponse>('/assets', payload)
      .pipe(map((response) => this.mapAsset(response.data)));
  }

  updateAsset(
    id: number,
    payload: UpdateAssetRequest,
  ): Observable<Asset> {
    return this.api
      .put<AssetResponse>(`/assets/${id}`, payload)
      .pipe(map((response) => this.mapAsset(response.data)));
  }

  deleteAsset(id: number): Observable<void> {
    return this.api.delete<void>(`/assets/${id}`);
  }

  private mapAsset(resource: AssetApiResource): Asset {
    return {
      id: resource.id,
      plantId: resource.plant_id,
      name: resource.name,
      assetType: resource.asset_type,
      serialNumber: resource.serial_number,
      status: resource.status,
      location: resource.location,
      createdAt: resource.created_at,
      updatedAt: resource.updated_at,
    };
  }
}