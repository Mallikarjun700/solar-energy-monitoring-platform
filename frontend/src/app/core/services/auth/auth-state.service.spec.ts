import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';

import { AuthStateService } from './auth-state.service';
import { AuthService } from './auth.service';

describe('AuthStateService', () => {
  let service: AuthStateService;
  let authService: {
    getToken: ReturnType<typeof vi.fn>;
    me: ReturnType<typeof vi.fn>;
  };

  const user = {
    id: 1,
    name: 'Admin',
    email: 'admin@example.com',
    role: 'viewer' as const,
  };

  beforeEach(() => {
    authService = {
      getToken: vi.fn(),
      me: vi.fn(),
    };

    TestBed.configureTestingModule({
      providers: [
        AuthStateService,
        {
          provide: AuthService,
          useValue: authService,
        },
      ],
    });

    service = TestBed.inject(AuthStateService);
  });

  it('starts unauthenticated', () => {
    expect(service.isAuthenticated()).toBe(false);
    expect(service.user()).toBeNull();
    expect(service.abilities()).toEqual([]);
    expect(service.role()).toBeNull();
  });

  it('sets authenticated user state', () => {
    service.setAuthenticatedUser(user, [
      'telemetry:read',
      'alerts:read',
    ]);

    expect(service.isAuthenticated()).toBe(true);
    expect(service.user()).toEqual(user);
    expect(service.role()).toBe('viewer');
    expect(service.abilities()).toEqual([
      'telemetry:read',
      'alerts:read',
    ]);
  });

  it('checks abilities', () => {
    service.setAuthenticatedUser(user, [
      'telemetry:read',
      'alerts:read',
    ]);

    expect(service.hasAbility('telemetry:read')).toBe(true);
    expect(service.hasAbility('telemetry:write')).toBe(false);
  });

  it('checks any ability', () => {
    service.setAuthenticatedUser(user, [
      'telemetry:read',
    ]);

    expect(
      service.hasAnyAbility([
        'telemetry:write',
        'telemetry:read',
      ]),
    ).toBe(true);

    expect(
      service.hasAnyAbility([
        'alerts:resolve',
        'telemetry:write',
      ]),
    ).toBe(false);
  });

  it('checks all abilities', () => {
    service.setAuthenticatedUser(user, [
      'telemetry:read',
      'alerts:read',
    ]);

    expect(
      service.hasAllAbilities([
        'telemetry:read',
        'alerts:read',
      ]),
    ).toBe(true);

    expect(
      service.hasAllAbilities([
        'telemetry:read',
        'telemetry:write',
      ]),
    ).toBe(false);
  });

  it('initializes from the current token', () => {
    authService.getToken.mockReturnValue('test-token');

    authService.me.mockReturnValue(
      of({
        status: 'success',
        message: 'Authenticated user.',
        data: {
          user,
          abilities: ['telemetry:read'],
        },
      }),
    );

    service.initialize().subscribe();

    expect(authService.me).toHaveBeenCalled();
    expect(service.isAuthenticated()).toBe(true);
    expect(service.user()).toEqual(user);
    expect(service.abilities()).toEqual([
      'telemetry:read',
    ]);
  });

  it('clears state', () => {
    service.setAuthenticatedUser(user, [
      'telemetry:read',
    ]);

    service.clearState();

    expect(service.isAuthenticated()).toBe(false);
    expect(service.user()).toBeNull();
    expect(service.abilities()).toEqual([]);
  });
});
