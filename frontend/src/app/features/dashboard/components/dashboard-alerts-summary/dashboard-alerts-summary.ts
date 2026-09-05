import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

import { AlertSeverity, DashboardAlert } from '../../models/dashboard-alert.model';

@Component({
  selector: 'app-dashboard-alerts-summary',
  standalone: true,
  templateUrl: './dashboard-alerts-summary.html',
  styleUrl: './dashboard-alerts-summary.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardAlertsSummaryComponent {
  readonly alerts = input.required<DashboardAlert[]>();

  readonly criticalCount = computed(() => this.countBySeverity('critical'));

  readonly warningCount = computed(() => this.countBySeverity('warning'));

  readonly infoCount = computed(() => this.countBySeverity('info'));

  private countBySeverity(severity: AlertSeverity): number {
    return this.alerts().filter((alert) => alert.severity === severity).length;
  }

  formatTimestamp(timestamp: string): string {
    const date = new Date(timestamp);

    if (Number.isNaN(date.getTime())) {
      return '—';
    }

    return date.toLocaleString([], {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  trackByAlertId(_: number, alert: DashboardAlert): number {
    return alert.id;
  }
}
