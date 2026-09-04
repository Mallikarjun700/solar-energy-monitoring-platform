import { Routes } from '@angular/router';
import { AppShell } from './core/layout/app-shell/app-shell';
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
    path: '',
    component: AppShell,
    canActivate: [authGuard],
    children: [
      {
        path: '',
        pathMatch: 'full',
        redirectTo: 'dashboard',
      },
      {
        path: 'dashboard',
        loadComponent: () =>
          import('./features/dashboard/dashboard.component').then(
            (m) => m.DashboardComponent,
          ),
      },
      {
        path: 'plants',
        loadComponent: () =>
          import('./features/plants/plants.component').then((m) => m.PlantsComponent),
      },
      {
        path: 'devices',
        loadComponent: () =>
          import('./features/devices/devices.component').then((m) => m.DevicesComponent),
      },
      {
        path: 'telemetry',
        canActivate: [abilityGuard(['telemetry:read'])],
        loadComponent: () =>
          import('./features/telemetry/telemetry.component').then((m) => m.TelemetryComponent),
      },
      {
        path: 'alerts',
        canActivate: [abilityGuard(['alerts:read'])],
        loadComponent: () =>
          import('./features/alerts/alerts.component').then((m) => m.AlertsComponent),
      },
      {
        path: 'administration',
        canActivate: [roleGuard(['admin'])],
        loadComponent: () =>
          import('./features/admin/admin.component').then((m) => m.AdminComponent),
      },
      {
        path: 'forbidden',
        loadComponent: () =>
          import('./features/forbidden/forbidden.component').then((m) => m.ForbiddenComponent),
      },
    ],
  },

  {
    path: '**',
    redirectTo: 'dashboard',
  },
];
