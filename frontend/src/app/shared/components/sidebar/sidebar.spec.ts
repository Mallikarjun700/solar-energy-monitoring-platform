import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { describe, expect, it } from 'vitest';
import { Sidebar } from './sidebar';
import { AuthStateService } from '../../../core/services/auth/auth-state.service';

describe('Sidebar', () => {
  let fixture: ComponentFixture<Sidebar>;

  let authState: {
    role: () => string | null;
    hasAbility: (ability: string) => boolean;
  };

  beforeEach(async () => {
    authState = {
      role: () => 'admin',
      hasAbility: () => true,
    };

    await TestBed.configureTestingModule({
      imports: [Sidebar],
      providers: [
        provideRouter([]),
        {
          provide: AuthStateService,
          useValue: authState,
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(Sidebar);
    fixture.detectChanges();
  });

  it('should render all navigation items for an admin', () => {
    const links = fixture.nativeElement.querySelectorAll('.app-sidebar__link');

    expect(links.length).toBe(6);
  });

  it('should hide administration for non-admin users', () => {
    authState.role = () => 'operator';

    fixture.detectChanges();

    const links = Array.from(
      fixture.nativeElement.querySelectorAll('.app-sidebar__link'),
    ) as HTMLAnchorElement[];

    expect(links.some((link) => link.textContent?.includes('Administration'))).toBe(false);
  });

  it('should hide telemetry when telemetry:read is unavailable', () => {
    authState.hasAbility = (ability: string) => ability !== 'telemetry:read';

    fixture.detectChanges();

    const links = Array.from(
      fixture.nativeElement.querySelectorAll('.app-sidebar__link'),
    ) as HTMLAnchorElement[];

    expect(links.some((link) => link.textContent?.includes('Telemetry'))).toBe(false);
  });

  it('should hide alerts when alerts:read is unavailable', () => {
    authState.hasAbility = (ability: string) => ability !== 'alerts:read';

    fixture.detectChanges();

    const links = Array.from(
      fixture.nativeElement.querySelectorAll('.app-sidebar__link'),
    ) as HTMLAnchorElement[];

    expect(links.some((link) => link.textContent?.includes('Alerts'))).toBe(false);
  });

  it('should expose the expected routes', () => {
    const links = Array.from(
      fixture.nativeElement.querySelectorAll('.app-sidebar__link'),
    ) as HTMLAnchorElement[];

    const routes = links.map((link) => link.getAttribute('href'));

    expect(routes).toContain('/dashboard');
    expect(routes).toContain('/plants');
    expect(routes).toContain('/devices');
    expect(routes).toContain('/telemetry');
    expect(routes).toContain('/alerts');
    expect(routes).toContain('/administration');
  });
});
