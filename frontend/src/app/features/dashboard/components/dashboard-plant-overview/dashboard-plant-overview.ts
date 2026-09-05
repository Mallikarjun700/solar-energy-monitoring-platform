import { ChangeDetectionStrategy, Component, input } from '@angular/core';

import { DashboardPlant } from '../../models/dashboard-plant.model';

@Component({
  selector: 'app-dashboard-plant-overview',
  standalone: true,
  templateUrl: './dashboard-plant-overview.html',
  styleUrl: './dashboard-plant-overview.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardPlantOverviewComponent {
  readonly plants = input.required<DashboardPlant[]>();

  formatCapacity(value: number | null): string {
    return value === null ? '—' : `${value.toLocaleString()} kW`;
  }

  formatPower(value: number | null): string {
    return value === null ? '—' : `${value.toLocaleString()} kW`;
  }

  formatPerformance(value: number | null): string {
    return value === null ? '—' : `${value.toLocaleString()}%`;
  }

  trackByPlantId(_: number, plant: DashboardPlant): number {
    return plant.id;
  }
}
