export interface DeviceFilters {
  search: string;
  status: string;
  assetId: number | null;
}

export const DEFAULT_DEVICE_FILTERS: DeviceFilters = {
  search: '',
  status: '',
  assetId: null,
};
