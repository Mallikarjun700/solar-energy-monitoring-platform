import { Injectable, inject } from '@angular/core';
import { Observable, tap } from 'rxjs';

import { ApiService } from '../api.service';
import { ApiResponse } from '../../models/api-response.model';
import {
  AuthData,
  AuthMeData,
} from '../../models/auth/auth-response.model';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private readonly api = inject(ApiService);

  private readonly tokenKey = 'solar_energy_access_token';

  login(email: string, password: string): Observable<ApiResponse<AuthData>> {
    return this.api
      .post<ApiResponse<AuthData>>('/auth/login', {
        email,
        password,
      })
      .pipe(
        tap((response) => {
          this.setToken(response.data.token);
        }),
      );
  }

  me(): Observable<ApiResponse<AuthMeData>> {
    return this.api.get<ApiResponse<AuthMeData>>('/auth/me');
  }

  logout(): Observable<unknown> {
    return this.api.post<unknown>('/auth/logout', {}).pipe(
      tap(() => {
        this.clearToken();
      }),
    );
  }

  getToken(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  isAuthenticated(): boolean {
    return this.getToken() !== null;
  }

  setToken(token: string): void {
    localStorage.setItem(this.tokenKey, token);
  }

  clearToken(): void {
    localStorage.removeItem(this.tokenKey);
  }

  getCurrentUser(): Observable<ApiResponse<AuthMeData>> {
    return this.me();
  }
}
