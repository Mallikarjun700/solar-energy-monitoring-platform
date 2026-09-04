import { Component } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, RouterOutlet } from '@angular/router';
import { describe, expect, it } from 'vitest';
import { AppShell } from './app-shell';
import { Header } from '../../../shared/components/header/header';
import { Sidebar } from '../../../shared/components/sidebar/sidebar';
import { AuthSessionService } from '../../../core/services/auth/auth-session.service';
import { AuthStateService } from '../../../core/services/auth/auth-state.service';

@Component({
  standalone: true,
  template: '<router-outlet />',
  imports: [RouterOutlet],
})
class RouterHost {}

describe('AppShell', () => {
  let fixture: ComponentFixture<AppShell>;

  const authSession = {
    logout: () => ({
      subscribe: () => undefined,
    }),
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AppShell, Header, Sidebar],
      providers: [
        provideRouter([]),
        {
          provide: AuthSessionService,
          useValue: authSession,
        },
        {
          provide: AuthStateService,
          useValue: {
            user: () => null,
            abilities: () => [],
            role: () => null,
            hasAbility: () => false,
          },
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(AppShell);
    fixture.detectChanges();
  });

  it('should create the application shell', () => {
    expect(fixture.componentInstance).toBeTruthy();
  });

  it('should render the header', () => {
    expect(fixture.nativeElement.querySelector('app-header')).toBeTruthy();
  });

  it('should render the sidebar', () => {
    expect(fixture.nativeElement.querySelector('app-sidebar')).toBeTruthy();
  });

  it('should render a router outlet', () => {
    expect(fixture.nativeElement.querySelector('router-outlet')).toBeTruthy();
  });
});
