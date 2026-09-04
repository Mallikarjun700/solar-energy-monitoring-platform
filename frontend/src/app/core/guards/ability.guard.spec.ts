import { TestBed } from '@angular/core/testing';
import { ActivatedRouteSnapshot, Router, RouterStateSnapshot } from '@angular/router';

import { abilityGuard } from './ability.guard';
import { AuthStateService } from '../services/auth/auth-state.service';

describe('abilityGuard', () => {
  let authState: {
    hasAllAbilities: ReturnType<typeof vi.fn>;
  };

  let router: {
    createUrlTree: ReturnType<typeof vi.fn>;
  };

  beforeEach(() => {
    authState = {
      hasAllAbilities: vi.fn(),
    };

    router = {
      createUrlTree: vi.fn(),
    };

    TestBed.configureTestingModule({
      providers: [
        {
          provide: AuthStateService,
          useValue: authState,
        },
        {
          provide: Router,
          useValue: router,
        },
      ],
    });
  });

  it('allows a user with the required ability', () => {
    authState.hasAllAbilities.mockReturnValue(true);

    const result = TestBed.runInInjectionContext(() =>
      abilityGuard(['telemetry:read'])({} as ActivatedRouteSnapshot, {} as RouterStateSnapshot),
    );

    expect(result).toBe(true);
    expect(router.createUrlTree).not.toHaveBeenCalled();
  });

  it('redirects a user without the required ability', () => {
    const forbiddenUrlTree = {
      redirect: '/forbidden',
    };

    authState.hasAllAbilities.mockReturnValue(false);
    router.createUrlTree.mockReturnValue(forbiddenUrlTree);

    const result = TestBed.runInInjectionContext(() =>
      abilityGuard(['telemetry:write'])({} as ActivatedRouteSnapshot, {} as RouterStateSnapshot),
    );

    expect(authState.hasAllAbilities).toHaveBeenCalledWith(['telemetry:write']);

    expect(router.createUrlTree).toHaveBeenCalledWith(['/forbidden']);

    expect(result).toBe(forbiddenUrlTree);
  });
});
