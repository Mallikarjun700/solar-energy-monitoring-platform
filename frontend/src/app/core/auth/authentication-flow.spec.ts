import { TestBed } from '@angular/core/testing';
import { of, Observable } from 'rxjs';

import { AuthService } from '../services/auth/auth.service';
import { AuthStateService } from '../services/auth/auth-state.service';
import { TokenStorageService } from './token-storage.service';
import { ApiResponse } from '../models/api-response.model';
import { AuthMeData } from '../models/auth/auth-response.model';

describe('Authentication flow', () => {
  let authService: {
    getToken: () => string | null;
    me: () => Observable<ApiResponse<AuthMeData>>;
  };

  let tokenStorage: TokenStorageService;
  let authState: AuthStateService;
  let storedToken: string | null;

  beforeEach(() => {
    storedToken = null;

    authService = {
      getToken: () => storedToken,
      me: () =>
        of({
          status: 'success',
          message: 'Authenticated user.',
          data: {
            user: {
              id: 1,
              name: 'Admin',
              email: 'admin@example.com',
              role: 'viewer',
            },
            abilities: ['telemetry:read'],
          },
        }),
    };

    TestBed.configureTestingModule({
      providers: [
        AuthStateService,
        TokenStorageService,
        {
          provide: AuthService,
          useValue: authService,
        },
      ],
    });

    tokenStorage = TestBed.inject(TokenStorageService);
    authState = TestBed.inject(AuthStateService);
  });

  afterEach(() => {
    localStorage.clear();
  });

  it('establishes authenticated state after login data is applied', () => {
    const user = {
      id: 1,
      name: 'Admin',
      email: 'admin@example.com',
      role: 'viewer' as const,
    };

    const abilities = ['telemetry:read', 'alerts:read'];

    tokenStorage.setToken('test-token');

    authState.setAuthenticatedUser(user, abilities);

    expect(tokenStorage.getToken()).toBe('test-token');
    expect(authState.isAuthenticated()).toBe(true);
    expect(authState.user()).toEqual(user);
    expect(authState.abilities()).toEqual(abilities);
  });

  it('restores authentication from the current user endpoint', () => {
    storedToken = 'existing-token';
    tokenStorage.setToken('existing-token');

    authState.initialize().subscribe();

    expect(authService.me).toBeDefined();
    expect(authState.isAuthenticated()).toBe(true);

    expect(authState.user()).toEqual({
      id: 1,
      name: 'Admin',
      email: 'admin@example.com',
      role: 'viewer',
    });

    expect(authState.abilities()).toEqual(['telemetry:read']);

    expect(authState.role()).toBe('viewer');
  });

  it('does not authenticate without a token', () => {
    authState.initialize().subscribe();

    expect(authState.isAuthenticated()).toBe(false);
    expect(authState.user()).toBeNull();
    expect(authState.abilities()).toEqual([]);
  });
});
