import { ChangeDetectionStrategy, Component, input } from '@angular/core';

import { DashboardKpis } from '../../models/dashboard-kpi.model';

@Component({
  selector: 'app-dashboard-kpi-cards',
  standalone: true,
  templateUrl: './dashboard-kpi-cards.html',
  styleUrl: './dashboard-kpi-cards.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardKpiCardsComponent {
  readonly kpis = input.required<DashboardKpis>();

  formatNumber(value: number | null): string {
    return value === null ? '—' : value.toLocaleString();
  }

  formatPower(value: number | null): string {
    return value === null ? '—' : `${value.toLocaleString()} kW`;
  }

  formatEnergy(value: number | null): string {
    return value === null ? '—' : `${value.toLocaleString()} kWh`;
  }
}
