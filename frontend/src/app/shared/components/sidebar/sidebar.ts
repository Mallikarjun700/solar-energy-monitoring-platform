import { Component, inject } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { AuthStateService } from '../../../core/services/auth/auth-state.service';

type NavigationRequirement =
  { type: 'public' } | { type: 'ability'; ability: string } | { type: 'role'; roles: string[] };

interface NavigationItem {
  label: string;
  route: string;
  icon: string;
  requirement: NavigationRequirement;
}

@Component({
  imports: [RouterLink, RouterLinkActive],
  selector: 'app-sidebar',
  styleUrl: './sidebar.scss',
  templateUrl: './sidebar.html',
})
export class Sidebar {
  protected readonly authState = inject(AuthStateService);

  protected readonly navigationItems: NavigationItem[] = [
    {
      label: 'Dashboard',
      route: '/dashboard',
      icon: '⌂',
      requirement: { type: 'public' },
    },
    {
      label: 'Plants',
      route: '/plants',
      icon: '▦',
      requirement: { type: 'public' },
    },
    {
      label: 'Devices',
      route: '/devices',
      icon: '◈',
      requirement: { type: 'public' },
    },
    {
      label: 'Telemetry',
      route: '/telemetry',
      icon: '⌁',
      requirement: {
        type: 'ability',
        ability: 'telemetry:read',
      },
    },
    {
      label: 'Alerts',
      route: '/alerts',
      icon: '!',
      requirement: {
        type: 'ability',
        ability: 'alerts:read',
      },
    },
    {
      label: 'Administration',
      route: '/administration',
      icon: '⚙',
      requirement: {
        type: 'role',
        roles: ['admin'],
      },
    },
  ];

  protected isVisible(item: NavigationItem): boolean {
    switch (item.requirement.type) {
      case 'public':
        return true;

      case 'ability':
        return this.authState.hasAbility(item.requirement.ability);

      case 'role':
        return item.requirement.roles.includes(this.authState.role() ?? '');

      default:
        return false;
    }
  }
}
