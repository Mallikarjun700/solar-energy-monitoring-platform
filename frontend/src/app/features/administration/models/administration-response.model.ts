import { AdminUser } from './admin-user.model';

export interface AdministrationApiResponse {
  status: string;
  message: string;
  data: {
    user: AdminUser;
    abilities: string[];
  };
}
