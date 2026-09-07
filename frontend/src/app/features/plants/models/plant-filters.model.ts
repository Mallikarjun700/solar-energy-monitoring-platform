export type PlantStatusFilters = {
  search: string;
  status: string;
};

export const DEFAULT_PLANT_FILTERS: PlantStatusFilters = {
  search: '',
  status: 'ALL',
};
