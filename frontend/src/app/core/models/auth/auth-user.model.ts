export type UserRole = 'admin' | 'operator' | 'viewer' | 'user';

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  tenant_id: string;
}
