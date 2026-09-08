import { DatePipe } from '@angular/common';

import { ChangeDetectionStrategy, Component, Input } from '@angular/core';

import { TelemetryEvent } from '../../models/telemetry-event.model';

@Component({
  selector: 'app-telemetry-readings',
  standalone: true,
  imports: [DatePipe],
  templateUrl: './telemetry-readings.html',
  styleUrl: './telemetry-readings.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TelemetryReadingsComponent {
  @Input({ required: true }) events: TelemetryEvent[] = [];

  expandedEventId: number | null = null;

  toggleDetails(eventId: number): void {
    this.expandedEventId = this.expandedEventId === eventId ? null : eventId;
  }

  isExpanded(eventId: number): boolean {
    return this.expandedEventId === eventId;
  }

  formatJson(value: Record<string, unknown> | null): string {
    if (!value || Object.keys(value).length === 0) {
      return 'No data';
    }

    return JSON.stringify(value, null, 2);
  }

  trackByEventId(_index: number, event: TelemetryEvent): number {
    return event.id;
  }
}
