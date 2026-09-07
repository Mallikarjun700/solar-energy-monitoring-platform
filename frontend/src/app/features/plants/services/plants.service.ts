import { Injectable, inject } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { Observable, catchError, map, throwError } from 'rxjs';

import { ApiService } from '../../../core/services/api.service';
import { ApiErrorService } from '../../../core/services/api-error.service';
import { Plant } from '../models/plant.model';

interface PlantApiResource {
  id: number;
  name: string;
  code: string;
  location: string | null;
  capacity_kw: number | string | null;
  status: string;
  created_at: string;
  updated_at: string;
}

interface PlantsApiResponse {
  data: PlantApiResource[];
}

interface PlantApiResponse {
  data: PlantApiResource;
}

@Injectable({
  providedIn: 'root',
})
export class PlantsService {
  private readonly api = inject(ApiService);
  private readonly apiErrorService = inject(ApiErrorService);

  getPlants(): Observable<Plant[]> {
    return this.api.get<PlantsApiResponse>('/plants').pipe(
      map((response) => response.data.map((plant) => this.mapPlant(plant))),
      catchError((error: HttpErrorResponse) =>
        throwError(() => this.apiErrorService.normalize(error)),
      ),
    );
  }

  getPlant(id: number): Observable<Plant> {
    return this.api.get<PlantApiResponse>(`/plants/${id}`).pipe(
      map((response) => this.mapPlant(response.data)),
      catchError((error: HttpErrorResponse) =>
        throwError(() => this.apiErrorService.normalize(error)),
      ),
    );
  }

  private mapPlant(resource: PlantApiResource): Plant {
    return {
      id: resource.id,
      name: resource.name,
      code: resource.code,
      location: resource.location,
      capacityKw: resource.capacity_kw === null ? null : Number(resource.capacity_kw),
      status: resource.status,
      createdAt: resource.created_at,
      updatedAt: resource.updated_at,
    };
  }
}
