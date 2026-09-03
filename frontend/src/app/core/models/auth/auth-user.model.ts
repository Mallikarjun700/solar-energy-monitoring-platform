export type UserRole = 'admin' | 'operator' | 'viewer';

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: UserRole;
}
