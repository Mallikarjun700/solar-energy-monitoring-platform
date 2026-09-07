import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { DecimalPipe } from '@angular/common';

import { TelemetryFilters } from '../../models/telemetry-filters.model';
import { TelemetryPaginationMeta } from '../../models/telemetry-response.model';

@Component({
  selector: 'app-telemetry-summary',
  standalone: true,
  imports: [DecimalPipe],
  templateUrl: './telemetry-summary.html',
  styleUrl: './telemetry-summary.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TelemetrySummaryComponent {
  @Input({ required: true }) pagination!: TelemetryPaginationMeta | null;

  @Input({ required: true }) filters!: TelemetryFilters;

  get totalEvents(): number {
    return this.pagination?.total ?? 0;
  }

  get currentPage(): number {
    return this.pagination?.current_page ?? 1;
  }

  get lastPage(): number {
    return this.pagination?.last_page ?? 1;
  }

  get pageSize(): number {
    return this.pagination?.per_page ?? this.filters.perPage;
  }

  get resultFrom(): number {
    return this.pagination?.from ?? 0;
  }

  get resultTo(): number {
    return this.pagination?.to ?? 0;
  }

  get activeFilterCount(): number {
    let count = 0;

    if (this.filters.sourceId) {
      count++;
    }

    if (this.filters.eventType) {
      count++;
    }

    if (this.filters.from) {
      count++;
    }

    if (this.filters.to) {
      count++;
    }

    return count;
  }

  get filterDescription(): string {
    if (this.activeFilterCount === 0) {
      return 'All available telemetry events';
    }

    if (this.activeFilterCount === 1) {
      return '1 active filter';
    }

    return `${this.activeFilterCount} active filters`;
  }
}
