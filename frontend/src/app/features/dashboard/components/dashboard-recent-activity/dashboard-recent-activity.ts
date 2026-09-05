import { ChangeDetectionStrategy, Component, input } from '@angular/core';

import { DashboardActivity } from '../../models/dashboard-activity.model';

@Component({
  selector: 'app-dashboard-recent-activity',
  standalone: true,
  templateUrl: './dashboard-recent-activity.html',
  styleUrl: './dashboard-recent-activity.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardRecentActivityComponent {
  readonly activities = input.required<DashboardActivity[]>();

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

  trackByActivityId(_: number, activity: DashboardActivity): string | number {
    return activity.id;
  }
}
