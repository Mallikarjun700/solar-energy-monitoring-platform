import { Component, inject, signal } from '@angular/core';
import { AuthSessionService } from '../../../core/services/auth/auth-session.service';
import { AuthStateService } from '../../../core/services/auth/auth-state.service';

@Component({
  imports: [],
  selector: 'app-header',
  styleUrl: './header.scss',
  templateUrl: './header.html',
})
export class Header {
  private readonly authSession = inject(AuthSessionService);
  protected readonly authState = inject(AuthStateService);

  protected readonly userMenuOpen = signal(false);

  protected toggleUserMenu(): void {
    this.userMenuOpen.update((open) => !open);
  }

  protected closeUserMenu(): void {
    this.userMenuOpen.set(false);
  }

  protected logout(): void {
    this.closeUserMenu();
    this.authSession.logout().subscribe();
  }
}
