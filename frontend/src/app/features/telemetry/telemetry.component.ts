import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';

import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { ErrorState } from '../../shared/components/error-state/error-state';
import { LoadingState } from '../../shared/components/loading-state/loading-state';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { DEFAULT_TELEMETRY_FILTERS, TelemetryFilters } from './models/telemetry-filters.model';
import { TelemetryEvent } from './models/telemetry-event.model';
import { TelemetryPaginationMeta } from './models/telemetry-response.model';
import { TelemetryEventQuery, TelemetryService } from './services/telemetry.service';
import { TelemetryFiltersComponent } from './components/telemetry-filters/telemetry-filters';
import { TelemetrySummaryComponent } from './components/telemetry-summary/telemetry-summary';
import { TelemetryReadingsComponent } from './components/telemetry-readings/telemetry-readings';
import { TelemetryTrendComponent } from './components/telemetry-trend/telemetry-trend';

@Component({
  selector: 'app-telemetry',
  standalone: true,
  imports: [
    PageHeader,
    LoadingState,
    EmptyState,
    ErrorState,
    TelemetryFiltersComponent,
    TelemetrySummaryComponent,
    TelemetryReadingsComponent,
    TelemetryTrendComponent,
  ],
  templateUrl: './telemetry.component.html',
  styleUrl: './telemetry.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TelemetryComponent {
  private readonly telemetryService = inject(TelemetryService);

  readonly events = signal<TelemetryEvent[]>([]);

  readonly pagination = signal<TelemetryPaginationMeta | null>(null);

  readonly filters = signal<TelemetryFilters>({
    ...DEFAULT_TELEMETRY_FILTERS,
  });

  readonly loading = signal(false);

  readonly initialLoading = signal(true);

  readonly refreshing = signal(false);

  readonly error = signal<string | null>(null);

  readonly hasEvents = computed(() => this.events().length > 0);

  readonly hasActiveFilters = computed(() => {
    const current = this.filters();

    return Boolean(current.sourceId || current.eventType || current.from || current.to);
  });

  readonly resultCount = computed(() => this.events().length);

  readonly currentPage = computed(() => this.pagination()?.current_page ?? 1);

  readonly lastPage = computed(() => this.pagination()?.last_page ?? 1);

  readonly hasPreviousPage = computed(() => this.currentPage() > 1);

  readonly hasNextPage = computed(() => this.currentPage() < this.lastPage());

  constructor() {
    this.loadEvents();
  }

  loadEvents(): void {
    this.loadPage(1);
  }

  retry(): void {
    this.loadPage(this.currentPage());
  }

  updateFilters(changes: Partial<TelemetryFilters>): void {
    this.filters.update((current) => ({
      ...current,
      ...changes,
    }));
  }

  applyFilters(): void {
    this.loadPage(1);
  }

  clearFilters(): void {
    this.filters.set({
      ...DEFAULT_TELEMETRY_FILTERS,
    });

    this.loadPage(1);
  }

  goToPreviousPage(): void {
    if (this.loading() || !this.hasPreviousPage()) {
      return;
    }

    this.loadPage(this.currentPage() - 1);
  }

  goToNextPage(): void {
    if (this.loading() || !this.hasNextPage()) {
      return;
    }

    this.loadPage(this.currentPage() + 1);
  }

  private loadPage(page: number): void {
    if (this.loading()) {
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    if (this.initialLoading()) {
      this.initialLoading.set(true);
    } else {
      this.refreshing.set(true);
    }

    const currentFilters = this.filters();

    const query: TelemetryEventQuery = {
      sourceId: currentFilters.sourceId || undefined,
      eventType: currentFilters.eventType || undefined,
      from: currentFilters.from || undefined,
      to: currentFilters.to || undefined,
      perPage: currentFilters.perPage,
      page,
    };

    this.telemetryService.getEvents(query).subscribe({
      next: (result) => {
        this.events.set(result.events);
        this.pagination.set(result.pagination);

        this.loading.set(false);
        this.initialLoading.set(false);
        this.refreshing.set(false);
      },
      error: (error: { message?: string }) => {
        this.loading.set(false);
        this.initialLoading.set(false);
        this.refreshing.set(false);

        this.error.set(error?.message ?? 'Unable to load telemetry events.');
      },
    });
  }
}
