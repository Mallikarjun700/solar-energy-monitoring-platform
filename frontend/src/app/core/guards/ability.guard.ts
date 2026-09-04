import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

import { AuthStateService } from '../services/auth/auth-state.service';

export const abilityGuard = (
  requiredAbilities: string[],
): CanActivateFn => {
  return () => {
    const authState = inject(AuthStateService);
    const router = inject(Router);

    if (authState.hasAllAbilities(requiredAbilities)) {
      return true;
    }

    return router.createUrlTree(['/forbidden']);
  };
};
