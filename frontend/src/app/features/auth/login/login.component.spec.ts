import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { of, throwError } from 'rxjs';

import { LoginComponent } from './login.component';
import { AuthService } from '../../../core/services/auth/auth.service';
import { AuthStateService } from '../../../core/services/auth/auth-state.service';
import { ApiErrorService } from '../../../core/services/api-error.service';

describe('LoginComponent', () => {
  let fixture: ComponentFixture<LoginComponent>;
  let component: LoginComponent;

  let authService: {
    login: ReturnType<typeof vi.fn>;
  };

  let authState: {
    setAuthenticatedUser: ReturnType<typeof vi.fn>;
  };

  let apiErrorService: {
    normalize: ReturnType<typeof vi.fn>;
  };

  let router: {
    navigate: ReturnType<typeof vi.fn>;
  };

  beforeEach(async () => {

    router = {
      navigate: vi.fn().mockResolvedValue(true),
    };

    authService = {
      login: vi.fn(),
    };

    authState = {
      setAuthenticatedUser: vi.fn(),
    };

    apiErrorService = {
      normalize: vi.fn().mockReturnValue({
        message: 'Invalid credentials.',
      }),
    };

    await TestBed.configureTestingModule({
      imports: [LoginComponent],
      providers: [
        {
          provide: Router,
          useValue: router,
        },
        {
          provide: AuthService,
          useValue: authService,
        },
        {
          provide: AuthStateService,
          useValue: authState,
        },
        {
          provide: ApiErrorService,
          useValue: apiErrorService,
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(LoginComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('creates the login form', () => {
    expect(component.loginForm).toBeDefined();
  });

  it('rejects an invalid form', () => {
    component.submit();

    expect(authService.login).not.toHaveBeenCalled();
  });

  it('logs in successfully', () => {
    authService.login.mockReturnValue(
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
          abilities: [
            'telemetry:read',
            'alerts:read',
          ],
        },
      }),
    );

    component.loginForm.setValue({
      email: 'admin@example.com',
      password: 'password',
    });

    component.submit();

    expect(router.navigate).toHaveBeenCalledWith([
      '/dashboard',
    ]);
    expect(authService.login).toHaveBeenCalledWith(
      'admin@example.com',
      'password',
    );

    expect(
      authState.setAuthenticatedUser,
    ).toHaveBeenCalledWith(
      {
        id: 1,
        name: 'Admin',
        email: 'admin@example.com',
        role: 'viewer',
      },
      [
        'telemetry:read',
        'alerts:read',
      ],
    );
  });

  it('shows an API error when login fails', () => {
    authService.login.mockReturnValue(
      throwError(() => new Error('Unauthorized')),
    );

    component.loginForm.setValue({
      email: 'admin@example.com',
      password: 'wrong-password',
    });

    component.submit();

    expect(component.errorMessage).toBe(
      'Invalid credentials.',
    );
  });
});
