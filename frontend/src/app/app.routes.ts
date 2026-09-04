import { Routes } from '@angular/router';

import { authGuard } from './core/guards/auth.guard';
import { abilityGuard } from './core/guards/ability.guard';
import { roleGuard } from './core/guards/role.guard';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () =>
      import('./features/auth/login/login.component').then((m) => m.LoginComponent),
  },

  {
    path: 'dashboard',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./features/dashboard/dashboard.component').then((m) => m.DashboardComponent),
  },

  {
    path: 'plants',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./features/plants/plants.component').then((m) => m.PlantsComponent),
  },

  {
    path: 'devices',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./features/devices/devices.component').then((m) => m.DevicesComponent),
  },

  {
    path: 'telemetry',
    canActivate: [authGuard, abilityGuard(['telemetry:read'])],
    loadComponent: () =>
      import('./features/telemetry/telemetry.component').then((m) => m.TelemetryComponent),
  },

  {
    path: 'alerts',
    canActivate: [authGuard, abilityGuard(['alerts:read'])],
    loadComponent: () =>
      import('./features/alerts/alerts.component').then((m) => m.AlertsComponent),
  },

  {
    path: 'administration',
    canActivate: [authGuard, roleGuard(['admin'])],
    loadComponent: () => import('./features/admin/admin.component').then((m) => m.AdminComponent),
  },

  {
    path: 'forbidden',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./features/forbidden/forbidden.component').then((m) => m.ForbiddenComponent),
  },

  {
    path: '',
    pathMatch: 'full',
    redirectTo: 'dashboard',
  },

  {
    path: '**',
    redirectTo: 'dashboard',
  },
];
