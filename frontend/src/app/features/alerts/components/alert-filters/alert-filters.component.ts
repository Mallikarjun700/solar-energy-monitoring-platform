import { ChangeDetectionStrategy, Component, EventEmitter, Output, input } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { TitleCasePipe } from '@angular/common';

import { AlertSeverity, AlertStatus } from '../../models/alert.model';
import { AlertFilters, DEFAULT_ALERT_FILTERS } from '../../models/alert-filters.model';

@Component({
  selector: 'app-alert-filters',
  standalone: true,
  imports: [FormsModule, TitleCasePipe],
  templateUrl: './alert-filters.component.html',
  styleUrl: './alert-filters.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AlertFiltersComponent {
  readonly initialFilters = input<AlertFilters>({
    ...DEFAULT_ALERT_FILTERS,
  });

  @Output() readonly filtersApplied = new EventEmitter<AlertFilters>();

  @Output() readonly filtersCleared = new EventEmitter<AlertFilters>();

  readonly statuses: AlertStatus[] = ['open', 'acknowledged', 'resolved'];

  readonly severities: AlertSeverity[] = ['info', 'warning', 'critical', 'emergency'];

  filters: AlertFilters = {
    ...DEFAULT_ALERT_FILTERS,
  };

  ngOnInit(): void {
    this.filters = {
      ...this.initialFilters(),
    };
  }

  apply(): void {
    this.filtersApplied.emit({
      ...this.filters,
      alertType: this.filters.alertType.trim(),
      deviceId: this.filters.deviceId.trim(),
      ruleId: this.filters.ruleId.trim(),
    });
  }

  clear(): void {
    this.filters = {
      ...DEFAULT_ALERT_FILTERS,
    };

    this.filtersCleared.emit({
      ...DEFAULT_ALERT_FILTERS,
    });
  }

  trackByValue(_index: number, value: string): string {
    return value;
  }
}
