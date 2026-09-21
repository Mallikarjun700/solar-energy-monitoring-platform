export type PlantStatus = 'ACTIVE' | 'INACTIVE' | 'MAINTENANCE';

export interface Plant {
  id: number;
  name: string;
  code: string;
  location: string | null;
  capacityKw: number | null;
  status: PlantStatus;
  createdAt: string;
  updatedAt: string;
}

export interface CreatePlantRequest {
  name: string;
  code: string;
  location?: string | null;
  capacity_kw?: number | null;
  status?: PlantStatus;
}
