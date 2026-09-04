import { Injectable, computed, inject, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';

import { AuthService } from './auth.service';
import { AuthUser } from '../../models/auth/auth-user.model';

@Injectable({
  providedIn: 'root',
})
export class AuthStateService {
  private readonly authService = inject(AuthService);

  private readonly userSignal = signal<AuthUser | null>(null);
  private readonly abilitiesSignal = signal<string[]>([]);
  private readonly authenticatedSignal = signal(false);

  readonly user = this.userSignal.asReadonly();
  readonly abilities = this.abilitiesSignal.asReadonly();
  readonly isAuthenticated = this.authenticatedSignal.asReadonly();

  readonly role = computed(() => this.userSignal()?.role ?? null);

  initialize(): Observable<unknown> {
    const token = this.authService.getToken();

    if (!token) {
      this.clearState();

      return new Observable((subscriber) => {
        subscriber.next(null);
        subscriber.complete();
      });
    }

    return this.authService.me().pipe(
      tap((response) => {
        this.setState(
          response.data.user,
          response.data.abilities,
        );
      }),
    );
  }

  setAuthenticatedUser(
    user: AuthUser,
    abilities: string[],
  ): void {
    this.setState(user, abilities);
  }

  clearState(): void {
    this.userSignal.set(null);
    this.abilitiesSignal.set([]);
    this.authenticatedSignal.set(false);
  }

  logout(): void {
    this.clearState();
  }

  hasAbility(ability: string): boolean {
    return this.abilitiesSignal().includes(ability);
  }

  hasAnyAbility(abilities: string[]): boolean {
    return abilities.some((ability) =>
      this.hasAbility(ability),
    );
  }

  hasAllAbilities(abilities: string[]): boolean {
    return abilities.every((ability) =>
      this.hasAbility(ability),
    );
  }

  private setState(
    user: AuthUser,
    abilities: string[],
  ): void {
    this.userSignal.set(user);
    this.abilitiesSignal.set([...abilities]);
    this.authenticatedSignal.set(true);
  }
}
