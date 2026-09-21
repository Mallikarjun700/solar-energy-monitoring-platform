export type AssetStatus = 'ACTIVE' | 'INACTIVE' | 'MAINTENANCE';

export interface Asset {
  id: number;
  plantId: number;
  name: string;
  assetType: string;
  serialNumber: string | null;
  status: AssetStatus;
  location: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface CreateAssetRequest {
  plant_id: number;
  name: string;
  asset_type: string;
  serial_number?: string | null;
  status: AssetStatus;
  location?: string | null;
}

export type UpdateAssetRequest = Partial<CreateAssetRequest>;