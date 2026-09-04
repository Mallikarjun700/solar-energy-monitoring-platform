import { Injectable, inject } from '@angular/core';
import { Observable, tap } from 'rxjs';

import { AuthService } from './auth.service';
import { AuthStateService } from './auth-state.service';

@Injectable({
  providedIn: 'root',
})
export class AuthSessionService {
  private readonly authService = inject(AuthService);
  private readonly authState = inject(AuthStateService);

  logout(): Observable<unknown> {
    return this.authService.logout().pipe(
      tap({
        next: () => this.authState.logout(),
        error: () => this.authState.logout(),
      }),
    );
  }
}
