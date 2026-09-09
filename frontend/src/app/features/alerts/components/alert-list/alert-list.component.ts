import { ChangeDetectionStrategy, Component, EventEmitter, Output, input } from '@angular/core';

import { Alert, AlertSeverity, AlertStatus } from '../../models/alert.model';

@Component({
  selector: 'app-alert-list',
  standalone: true,
  templateUrl: './alert-list.component.html',
  styleUrl: './alert-list.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AlertListComponent {
  readonly alerts = input<Alert[]>([]);

  @Output() readonly alertSelected = new EventEmitter<Alert>();

  readonly trackByAlertId = (_index: number, alert: Alert): number => alert.id;

  severityLabel(severity: AlertSeverity): string {
    return this.capitalize(severity);
  }

  statusLabel(status: AlertStatus): string {
    return this.capitalize(status);
  }

  selectAlert(alert: Alert): void {
    this.alertSelected.emit(alert);
  }

  formatDate(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
      return value;
    }

    return new Intl.DateTimeFormat(undefined, {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(date);
  }

  private capitalize(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
  }
}
