import { TestBed } from '@angular/core/testing';
import { ActivatedRouteSnapshot, Router, RouterStateSnapshot } from '@angular/router';

import { authGuard } from './auth.guard';
import { AuthService } from '../services/auth/auth.service';

describe('authGuard', () => {
  let authService: {
    isAuthenticated: ReturnType<typeof vi.fn>;
  };

  let router: {
    createUrlTree: ReturnType<typeof vi.fn>;
  };

  beforeEach(() => {
    authService = {
      isAuthenticated: vi.fn(),
    };

    router = {
      createUrlTree: vi.fn(),
    };

    TestBed.configureTestingModule({
      providers: [
        {
          provide: AuthService,
          useValue: authService,
        },
        {
          provide: Router,
          useValue: router,
        },
      ],
    });
  });

  it('allows authenticated users', () => {
    authService.isAuthenticated.mockReturnValue(true);

    const result = TestBed.runInInjectionContext(() =>
      authGuard(
        {} as ActivatedRouteSnapshot,
        { url: '/dashboard' } as RouterStateSnapshot,
      ),
    );

    expect(result).toBe(true);
    expect(router.createUrlTree).not.toHaveBeenCalled();
  });

  it('redirects unauthenticated users to login', () => {
    const loginUrlTree = {
      redirect: '/login',
    };

    authService.isAuthenticated.mockReturnValue(false);
    router.createUrlTree.mockReturnValue(loginUrlTree);

    const result = TestBed.runInInjectionContext(() =>
      authGuard(
        {} as ActivatedRouteSnapshot,
        { url: '/dashboard' } as RouterStateSnapshot,
      ),
    );

    expect(router.createUrlTree).toHaveBeenCalledWith(
      ['/login'],
      {
        queryParams: {
          returnUrl: '/dashboard',
        },
      },
    );

    expect(result).toBe(loginUrlTree);
  });
});
