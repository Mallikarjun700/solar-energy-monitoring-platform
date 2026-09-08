import { ChangeDetectionStrategy, Component, Input, OnChanges, SimpleChanges } from '@angular/core';
import { DatePipe } from '@angular/common';

import { TelemetryEvent } from '../../models/telemetry-event.model';

interface TrendPoint {
  timestamp: string;
  label: string;
  count: number;
  percentage: number;
}

@Component({
  selector: 'app-telemetry-trend',
  standalone: true,
  imports: [DatePipe],
  templateUrl: './telemetry-trend.html',
  styleUrl: './telemetry-trend.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TelemetryTrendComponent implements OnChanges {
  @Input({ required: true }) events: TelemetryEvent[] = [];

  trendPoints: TrendPoint[] = [];

  maxCount = 0;

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['events']) {
      this.buildTrend();
    }
  }

  private buildTrend(): void {
    if (this.events.length === 0) {
      this.trendPoints = [];
      this.maxCount = 0;
      return;
    }

    const sortedEvents = [...this.events].sort(
      (a, b) => new Date(a.eventTimestamp).getTime() - new Date(b.eventTimestamp).getTime(),
    );

    const firstTimestamp = new Date(sortedEvents[0].eventTimestamp).getTime();

    const lastTimestamp = new Date(sortedEvents[sortedEvents.length - 1].eventTimestamp).getTime();

    const range = lastTimestamp - firstTimestamp;

    const bucketCount = Math.min(12, Math.max(1, sortedEvents.length));

    const bucketSize = range === 0 ? 1 : Math.max(1, Math.ceil(range / bucketCount));

    const buckets = new Map<number, number>();

    for (const event of sortedEvents) {
      const timestamp = new Date(event.eventTimestamp).getTime();

      let bucketStart: number;

      if (range === 0) {
        bucketStart = firstTimestamp;
      } else {
        bucketStart =
          firstTimestamp + Math.floor((timestamp - firstTimestamp) / bucketSize) * bucketSize;
      }

      buckets.set(bucketStart, (buckets.get(bucketStart) ?? 0) + 1);
    }

    const points = Array.from(buckets.entries())
      .sort(([a], [b]) => a - b)
      .map(([timestamp, count]) => ({
        timestamp: new Date(timestamp).toISOString(),
        label: new Date(timestamp).toLocaleString(),
        count,
        percentage: 0,
      }));

    this.maxCount = Math.max(...points.map((point) => point.count));

    this.trendPoints = points.map((point) => ({
      ...point,
      percentage: this.maxCount === 0 ? 0 : Math.max(8, (point.count / this.maxCount) * 100),
    }));
  }
}
