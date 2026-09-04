import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

import { AuthStateService } from '../services/auth/auth-state.service';
import { UserRole } from '../models/auth/auth-user.model';

export const roleGuard = (allowedRoles: UserRole[]): CanActivateFn => {
  return () => {
    const authState = inject(AuthStateService);
    const router = inject(Router);

    const role = authState.role();

    if (role && allowedRoles.includes(role)) {
      return true;
    }

    return router.createUrlTree(['/forbidden']);
  };
};
