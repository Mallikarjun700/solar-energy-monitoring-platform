import { AuthUser } from './auth-user.model';

export interface AuthState {
  isAuthenticated: boolean;
  user: AuthUser | null;
  abilities: string[];
}
