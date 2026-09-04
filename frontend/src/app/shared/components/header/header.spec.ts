import { ComponentFixture, TestBed } from '@angular/core/testing';
import { describe, expect, it, vi } from 'vitest';
import { Header } from './header';
import { AuthSessionService } from '../../../core/services/auth/auth-session.service';
import { AuthStateService } from '../../../core/services/auth/auth-state.service';

describe('Header', () => {
  let fixture: ComponentFixture<Header>;

  const logout = vi.fn(() => ({
    subscribe: vi.fn(),
  }));

  const authSession = {
    logout,
  };

  const authState = {
    user: () => ({
      id: 1,
      name: 'Admin User',
      email: 'admin@example.com',
      role: 'admin',
    }),
  };

  beforeEach(async () => {
    vi.clearAllMocks();

    await TestBed.configureTestingModule({
      imports: [Header],
      providers: [
        {
          provide: AuthSessionService,
          useValue: authSession,
        },
        {
          provide: AuthStateService,
          useValue: authState,
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(Header);
    fixture.detectChanges();
  });

  it('should display the authenticated user', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Admin User');
    expect(element.textContent).toContain('admin');
  });

  it('should open the user menu', () => {
    const trigger = fixture.nativeElement.querySelector(
      '.app-header__user-trigger',
    ) as HTMLButtonElement;

    expect(fixture.nativeElement.querySelector('.app-header__menu')).toBeNull();

    trigger.click();
    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('.app-header__menu')).toBeTruthy();
  });

  it('should close the user menu', () => {
    const trigger = fixture.nativeElement.querySelector(
      '.app-header__user-trigger',
    ) as HTMLButtonElement;

    trigger.click();
    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('.app-header__menu')).toBeTruthy();

    trigger.click();
    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('.app-header__menu')).toBeNull();
  });

  it('should invoke logout from the user menu', () => {
    const trigger = fixture.nativeElement.querySelector(
      '.app-header__user-trigger',
    ) as HTMLButtonElement;

    trigger.click();
    fixture.detectChanges();

    const logoutButton = fixture.nativeElement.querySelector(
      '.app-header__menu-action',
    ) as HTMLButtonElement;

    logoutButton.click();

    expect(logout).toHaveBeenCalledTimes(1);
  });
});
