import { Injectable, inject } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, tap } from 'rxjs';

import { AuthService } from './auth.service';
import { AuthStateService } from './auth-state.service';

@Injectable({
  providedIn: 'root',
})
export class AuthSessionService {
  private readonly authService = inject(AuthService);
  private readonly authState = inject(AuthStateService);
  private readonly router = inject(Router);

  logout(): Observable<unknown> {
    return this.authService.logout().pipe(
      tap({
        next: () => this.finishLogout(),
        error: () => this.finishLogout(),
      }),
    );
  }

  private finishLogout(): void {
    this.authState.logout();
    void this.router.navigate(['/login']);
  }
}
