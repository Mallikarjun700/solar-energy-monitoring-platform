import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';

import { TenantContextService } from '../../../../core/tenant/tenant-context.service';
import { AlertDetails } from '../../models/alert-details.model';
import { AlertsService } from '../../services/alerts.service';

@Component({
  selector: 'app-alert-details',
  standalone: true,
  templateUrl: './alert-details.component.html',
  styleUrl: './alert-details.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AlertDetailsComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly alertsService = inject(AlertsService);
  private readonly tenantContext = inject(TenantContextService);

  readonly alert = signal<AlertDetails | null>(null);
  readonly loading = signal(true);
  readonly error = signal<string | null>(null);

  readonly actionLoading = signal<'acknowledge' | 'resolve' | null>(null);

  readonly actionError = signal<string | null>(null);
  readonly actionSuccess = signal<string | null>(null);

  private alertId: number | null = null;

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    if (!Number.isInteger(id) || id < 1) {
      this.loading.set(false);
      this.error.set('Invalid alert ID.');
      return;
    }

    this.alertId = id;
    this.loadAlert();
  }

  loadAlert(): void {
    if (this.alertId === null) {
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    const tenantId = this.tenantContext.getTenantId();

    this.alertsService.getAlert(this.alertId, tenantId).subscribe({
      next: (alert) => {
        this.alert.set(alert);
        this.loading.set(false);
      },
      error: () => {
        this.alert.set(null);
        this.loading.set(false);
        this.error.set('Unable to load the alert details. Please try again.');
      },
    });
  }

  acknowledge(): void {
    const currentAlert = this.alert();

    if (!currentAlert || currentAlert.status !== 'open' || this.actionLoading()) {
      return;
    }

    this.actionLoading.set('acknowledge');
    this.actionError.set(null);
    this.actionSuccess.set(null);

    const tenantId = this.tenantContext.getTenantId();

    this.alertsService.acknowledgeAlert(currentAlert.id, tenantId).subscribe({
      next: (updatedAlert) => {
        this.alert.set({
          ...currentAlert,
          ...updatedAlert,
          rule: currentAlert.rule,
        });

        this.actionLoading.set(null);
        this.actionSuccess.set('Alert acknowledged successfully.');
      },
      error: (error) => {
        this.actionLoading.set(null);
        this.actionError.set(this.getActionError(error, 'Unable to acknowledge the alert.'));
      },
    });
  }

  resolve(): void {
    const currentAlert = this.alert();

    if (
      !currentAlert ||
      !['open', 'acknowledged'].includes(currentAlert.status) ||
      this.actionLoading()
    ) {
      return;
    }

    this.actionLoading.set('resolve');
    this.actionError.set(null);
    this.actionSuccess.set(null);

    const tenantId = this.tenantContext.getTenantId();

    this.alertsService.resolveAlert(currentAlert.id, tenantId).subscribe({
      next: (updatedAlert) => {
        this.alert.set({
          ...currentAlert,
          ...updatedAlert,
          rule: currentAlert.rule,
        });

        this.actionLoading.set(null);
        this.actionSuccess.set('Alert resolved successfully.');
      },
      error: (error) => {
        this.actionLoading.set(null);
        this.actionError.set(this.getActionError(error, 'Unable to resolve the alert.'));
      },
    });
  }

  retry(): void {
    this.loadAlert();
  }

  goBack(): void {
    this.router.navigate(['/alerts']);
  }

  canAcknowledge(): boolean {
    return this.alert()?.status === 'open';
  }

  canResolve(): boolean {
    const status = this.alert()?.status;

    return status === 'open' || status === 'acknowledged';
  }

  formatDate(value: string | null): string {
    if (!value) {
      return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
      return value;
    }

    return new Intl.DateTimeFormat(undefined, {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(date);
  }

  severityLabel(value: string): string {
    return this.capitalize(value);
  }

  statusLabel(value: string): string {
    return this.capitalize(value);
  }

  private getActionError(error: unknown, fallback: string): string {
    if (typeof error === 'object' && error !== null && 'status' in error && error.status === 409) {
      return 'The alert state has changed. Refresh the alert and try again.';
    }

    return fallback;
  }

  private capitalize(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
  }
}
