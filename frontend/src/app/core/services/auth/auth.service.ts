import { Injectable, inject } from '@angular/core';
import { Observable, tap } from 'rxjs';

import { ApiService } from '../api.service';
import { ApiResponse } from '../../models/api-response.model';
import {
  AuthData,
  AuthMeData,
} from '../../models/auth/auth-response.model';
import { TokenStorageService } from '../../auth/token-storage.service';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private readonly api = inject(ApiService);
  private readonly tokenStorage = inject(TokenStorageService);

  login(email: string, password: string): Observable<ApiResponse<AuthData>> {
    return this.api
      .post<ApiResponse<AuthData>>('/auth/login', {
        email,
        password,
      })
      .pipe(
        tap((response) => {
          this.tokenStorage.setToken(response.data.token);
        }),
      );
  }

  me(): Observable<ApiResponse<AuthMeData>> {
    return this.api.get<ApiResponse<AuthMeData>>('/auth/me');
  }

  logout(): Observable<unknown> {
    return this.api.post<unknown>('/auth/logout', {}).pipe(
      tap(() => {
        this.tokenStorage.clearToken();
      }),
    );
  }

  getToken(): string | null {
    return this.tokenStorage.getToken();
  }

  isAuthenticated(): boolean {
    return this.tokenStorage.hasToken();
  }
}
