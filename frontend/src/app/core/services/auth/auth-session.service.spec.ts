import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';

import { AuthSessionService } from './auth-session.service';
import { AuthService } from './auth.service';
import { AuthStateService } from './auth-state.service';

describe('AuthSessionService', () => {
  let service: AuthSessionService;

  let authService: {
    logout: ReturnType<typeof vi.fn>;
  };

  let authState: {
    logout: ReturnType<typeof vi.fn>;
  };

  beforeEach(() => {
    authService = {
      logout: vi.fn(),
    };

    authState = {
      logout: vi.fn(),
    };

    TestBed.configureTestingModule({
      providers: [
        AuthSessionService,
        {
          provide: AuthService,
          useValue: authService,
        },
        {
          provide: AuthStateService,
          useValue: authState,
        },
      ],
    });

    service = TestBed.inject(AuthSessionService);
  });

  it('clears authentication state after successful logout', () => {
    authService.logout.mockReturnValue(of(null));

    service.logout().subscribe();

    expect(authService.logout).toHaveBeenCalled();
    expect(authState.logout).toHaveBeenCalled();
  });

  it('clears authentication state when logout request fails', () => {
    authService.logout.mockReturnValue(throwError(() => new Error('Network error')));

    service.logout().subscribe({
      error: () => {
        expect(authState.logout).toHaveBeenCalled();
      },
    });
  });
});
