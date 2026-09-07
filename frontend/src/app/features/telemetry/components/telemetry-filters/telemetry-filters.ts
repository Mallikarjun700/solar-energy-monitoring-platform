import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';

import { TelemetryFilters } from '../../models/telemetry-filters.model';

@Component({
  selector: 'app-telemetry-filters',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './telemetry-filters.html',
  styleUrl: './telemetry-filters.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TelemetryFiltersComponent {
  @Input({ required: true }) filters!: TelemetryFilters;

  @Output() readonly filtersChange = new EventEmitter<Partial<TelemetryFilters>>();

  @Output() readonly apply = new EventEmitter<void>();

  @Output() readonly clear = new EventEmitter<void>();

  get hasDateRangeError(): boolean {
    if (!this.filters.from || !this.filters.to) {
      return false;
    }

    return this.filters.from > this.filters.to;
  }

  updateField(
    field: keyof Pick<TelemetryFilters, 'sourceId' | 'eventType' | 'from' | 'to'>,
    value: string,
  ): void {
    this.filtersChange.emit({
      [field]: value,
    });
  }

  applyFilters(): void {
    if (this.hasDateRangeError) {
      return;
    }

    this.apply.emit();
  }

  clearFilters(): void {
    this.clear.emit();
  }
}
