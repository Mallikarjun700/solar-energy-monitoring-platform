import { AuthUser } from './auth-user.model';

export interface AuthData {
  user: AuthUser;
  token: string;
  abilities: string[];
}

export interface AuthMeData {
  user: AuthUser;
  abilities: string[];
}
