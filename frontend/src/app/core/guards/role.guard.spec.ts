import { TestBed } from '@angular/core/testing';
import { ActivatedRouteSnapshot, Router, RouterStateSnapshot } from '@angular/router';

import { roleGuard } from './role.guard';
import { AuthStateService } from '../services/auth/auth-state.service';

describe('roleGuard', () => {
  let authState: {
    role: ReturnType<typeof vi.fn>;
  };

  let router: {
    createUrlTree: ReturnType<typeof vi.fn>;
  };

  beforeEach(() => {
    authState = {
      role: vi.fn(),
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

  it('allows an authorized role', () => {
    authState.role.mockReturnValue('admin');

    const result = TestBed.runInInjectionContext(() =>
      roleGuard(['admin'])(
        {} as ActivatedRouteSnapshot,
        {} as RouterStateSnapshot,
      ),
    );

    expect(result).toBe(true);
  });

  it('allows one of multiple authorized roles', () => {
    authState.role.mockReturnValue('operator');

    const result = TestBed.runInInjectionContext(() =>
      roleGuard(['admin', 'operator'])(
        {} as ActivatedRouteSnapshot,
        {} as RouterStateSnapshot,
      ),
    );

    expect(result).toBe(true);
  });

  it('redirects an unauthorized role', () => {
    const forbiddenUrlTree = {
      redirect: '/forbidden',
    };

    authState.role.mockReturnValue('viewer');
    router.createUrlTree.mockReturnValue(forbiddenUrlTree);

    const result = TestBed.runInInjectionContext(() =>
      roleGuard(['admin'])(
        {} as ActivatedRouteSnapshot,
        {} as RouterStateSnapshot,
      ),
    );

    expect(router.createUrlTree).toHaveBeenCalledWith([
      '/forbidden',
    ]);

    expect(result).toBe(forbiddenUrlTree);
  });
});
