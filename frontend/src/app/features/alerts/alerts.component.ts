import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { Router } from '@angular/router';

import { TenantContextService } from '../../core/tenant/tenant-context.service';
import { AlertListComponent } from './components/alert-list/alert-list.component';
import { AlertFiltersComponent } from './components/alert-filters/alert-filters.component';
import { AlertSummaryComponent } from './components/alert-summary/alert-summary.component';
import { AlertFilters, DEFAULT_ALERT_FILTERS } from './models/alert-filters.model';
import { Alert } from './models/alert.model';
import { AlertPaginationMeta } from './models/alert-response.model';
import { AlertsService } from './services/alerts.service';

@Component({
  selector: 'app-alerts',
  standalone: true,
  imports: [AlertFiltersComponent, AlertSummaryComponent, AlertListComponent],
  templateUrl: './alerts.component.html',
  styleUrl: './alerts.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AlertsComponent {
  private readonly alertsService = inject(AlertsService);

  private readonly tenantContext = inject(TenantContextService);

  private readonly router = inject(Router);

  readonly alerts = signal<Alert[]>([]);
  readonly pagination = signal<AlertPaginationMeta | null>(null);

  readonly filters = signal<AlertFilters>({
    ...DEFAULT_ALERT_FILTERS,
  });

  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  ngOnInit(): void {
    this.loadAlerts();
  }

  loadAlerts(): void {
    this.loading.set(true);
    this.error.set(null);

    const tenantId = this.tenantContext.getTenantId();

    this.alertsService.getAlerts(this.filters(), tenantId).subscribe({
      next: (response) => {
        this.alerts.set(response.alerts);
        this.pagination.set(response.pagination);
        this.loading.set(false);
      },
      error: () => {
        this.alerts.set([]);
        this.pagination.set(null);
        this.loading.set(false);

        this.error.set('Unable to load alerts. Please try again.');
      },
    });
  }

  refresh(): void {
    this.loadAlerts();
  }

  retry(): void {
    this.loadAlerts();
  }

  updateFilters(filters: AlertFilters): void {
    this.filters.set({
      ...filters,
    });

    this.loadAlerts();
  }

  clearFilters(): void {
    this.filters.set({
      ...DEFAULT_ALERT_FILTERS,
    });

    this.loadAlerts();
  }

  onAlertSelected(alert: Alert): void {
    this.router.navigate(['/alerts', alert.id]);
  }
}
