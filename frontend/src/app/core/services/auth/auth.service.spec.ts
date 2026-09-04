import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';

import { AuthService } from './auth.service';
import { ApiService } from '../api.service';
import { TokenStorageService } from '../../auth/token-storage.service';

describe('AuthService', () => {
  let service: AuthService;

  let api: {
    post: ReturnType<typeof vi.fn>;
    get: ReturnType<typeof vi.fn>;
  };

  let tokenStorage: {
    getToken: ReturnType<typeof vi.fn>;
    setToken: ReturnType<typeof vi.fn>;
    clearToken: ReturnType<typeof vi.fn>;
    hasToken: ReturnType<typeof vi.fn>;
  };

  beforeEach(() => {
    api = {
      post: vi.fn(),
      get: vi.fn(),
    };

    tokenStorage = {
      getToken: vi.fn(),
      setToken: vi.fn(),
      clearToken: vi.fn(),
      hasToken: vi.fn(),
    };

    TestBed.configureTestingModule({
      providers: [
        AuthService,
        {
          provide: ApiService,
          useValue: api,
        },
        {
          provide: TokenStorageService,
          useValue: tokenStorage,
        },
      ],
    });

    service = TestBed.inject(AuthService);
  });

  it('stores the token after successful login', () => {
    api.post.mockReturnValue(
      of({
        status: 'success',
        message: 'Login successful.',
        data: {
          user: {
            id: 1,
            name: 'Admin',
            email: 'admin@example.com',
            role: 'viewer',
          },
          token: 'test-token',
          abilities: ['telemetry:read'],
        },
      }),
    );

    service.login(
      'admin@example.com',
      'password',
    ).subscribe();

    expect(tokenStorage.setToken).toHaveBeenCalledWith(
      'test-token',
    );
  });

  it('clears the token after successful logout', () => {
    api.post.mockReturnValue(of(null));

    service.logout().subscribe();

    expect(api.post).toHaveBeenCalledWith(
      '/auth/logout',
      {},
    );

    expect(tokenStorage.clearToken).toHaveBeenCalled();
  });

  it('clears the token when logout fails', () => {
    const logoutError = new Error('Network error');

    api.post.mockReturnValue(
      throwError(() => logoutError),
    );

    service.logout().subscribe({
      error: (error) => {
        expect(error).toBe(logoutError);
      },
    });

    expect(tokenStorage.clearToken).toHaveBeenCalled();
  });

});
