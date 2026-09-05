import { ChangeDetectionStrategy, Component, input } from '@angular/core';

import { DashboardEnergyPoint } from '../../models/dashboard-view.model';

@Component({
  selector: 'app-dashboard-energy-trend',
  standalone: true,
  templateUrl: './dashboard-energy-trend.html',
  styleUrl: './dashboard-energy-trend.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardEnergyTrendComponent {
  readonly points = input.required<DashboardEnergyPoint[]>();

  formatEnergy(value: number | null): string {
    return value === null ? '—' : `${value.toLocaleString()} kWh`;
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

  trackByTimestamp(_: number, point: DashboardEnergyPoint): string {
    return point.timestamp;
  }
}
