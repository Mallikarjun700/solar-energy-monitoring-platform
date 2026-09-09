import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

import { Alert } from '../../models/alert.model';
import { AlertPaginationMeta } from '../../models/alert-response.model';
import { AlertSummary, buildAlertSummary } from './alert-summary.model';

@Component({
  selector: 'app-alert-summary',
  standalone: true,
  templateUrl: './alert-summary.component.html',
  styleUrl: './alert-summary.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AlertSummaryComponent {
  readonly alerts = input<Alert[]>([]);
  readonly pagination = input<AlertPaginationMeta | null>(null);

  readonly summary = computed<AlertSummary>(() =>
    buildAlertSummary(this.alerts(), this.pagination()),
  );
}
