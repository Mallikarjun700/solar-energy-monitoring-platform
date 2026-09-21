import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { ApiService } from '../../../core/services/api.service';
import {
  CreatePlantRequest,
  Plant,
  PlantStatus,
} from '../models/plant.model';

interface PlantApiResource {
  id: number;
  name: string;
  code: string;
  location: string | null;
  capacity_kw: number | string | null;
  status: PlantStatus;
  created_at: string;
  updated_at: string;
}

interface PlantListResponse {
  status?: string;
  message?: string;
  data: PlantApiResource[];
}

interface PlantResponse {
  status?: string;
  message?: string;
  data: PlantApiResource;
}

@Injectable({
  providedIn: 'root',
})
export class PlantsService {
  private readonly api = inject(ApiService);

  getPlants(): Observable<Plant[]> {
    return this.api
      .get<PlantListResponse>('/plants')
      .pipe(map((response) => response.data.map((plant) => this.mapPlant(plant))));
  }

  getPlant(id: number): Observable<Plant> {
    return this.api
      .get<PlantResponse>(`/plants/${id}`)
      .pipe(map((response) => this.mapPlant(response.data)));
  }

  createPlant(payload: CreatePlantRequest): Observable<Plant> {
    return this.api
      .post<PlantResponse>('/plants', payload)
      .pipe(map((response) => this.mapPlant(response.data)));
  }

  private mapPlant(resource: PlantApiResource): Plant {
    return {
      id: resource.id,
      name: resource.name,
      code: resource.code,
      location: resource.location,
      capacityKw:
        resource.capacity_kw === null
          ? null
          : Number(resource.capacity_kw),
      status: resource.status,
      createdAt: resource.created_at,
      updatedAt: resource.updated_at,
    };
  }
}
