export interface Plant {
  id: number;
  name: string;
  code: string;
  location: string | null;
  capacityKw: number | null;
  status: string;
  createdAt: string;
  updatedAt: string;
}
