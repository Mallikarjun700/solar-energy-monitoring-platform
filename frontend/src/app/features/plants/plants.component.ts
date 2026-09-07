import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { finalize } from 'rxjs';

import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { ErrorState } from '../../shared/components/error-state/error-state';
import { LoadingState } from '../../shared/components/loading-state/loading-state';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { ApiErrorService } from '../../core/services/api-error.service';
import { PlantsService } from './services/plants.service';
import { Plant } from './models/plant.model';
import { HttpErrorResponse } from '@angular/common/http';
import { PlantStatusFilters, DEFAULT_PLANT_FILTERS } from './models/plant-filters.model';
import { PlantFiltersComponent } from './components/plant-filters/plant-filters.component';
import { PlantListComponent } from './components/plant-list/plant-list.component';

@Component({
  selector: 'app-plants',
  standalone: true,
  imports: [
    PageHeader,
    LoadingState,
    EmptyState,
    ErrorState,
    PlantFiltersComponent,
    PlantListComponent,
  ],
  templateUrl: './plants.component.html',
  styleUrl: './plants.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PlantsComponent {
  private readonly plantsService = inject(PlantsService);
  private readonly apiErrorService = inject(ApiErrorService);

  readonly plants = signal<Plant[]>([]);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  readonly filters = signal<PlantStatusFilters>({
    ...DEFAULT_PLANT_FILTERS,
  });

  readonly filteredPlants = computed(() => {
    const { search, status } = this.filters();
    const normalizedSearch = search.trim().toLowerCase();

    return this.plants().filter((plant) => {
      const matchesSearch =
        normalizedSearch.length === 0 ||
        plant.name.toLowerCase().includes(normalizedSearch) ||
        plant.code.toLowerCase().includes(normalizedSearch) ||
        (plant.location ?? '').toLowerCase().includes(normalizedSearch);

      const matchesStatus = status === 'ALL' || plant.status === status;

      return matchesSearch && matchesStatus;
    });
  });

  constructor() {
    this.loadPlants();
  }

  loadPlants(): void {
    this.loading.set(true);
    this.error.set(null);

    this.plantsService
      .getPlants()
      .pipe(finalize(() => this.loading.set(false)))
      .subscribe({
        next: (plants) => {
          this.plants.set(plants);
        },
        error: (error: unknown) => {
          const normalizedError = this.apiErrorService.normalize(error as HttpErrorResponse);
          this.error.set(normalizedError.message);
        },
      });
  }

  updateSearch(search: string): void {
    this.filters.update((filters) => ({
      ...filters,
      search,
    }));
  }

  updateStatus(status: PlantStatusFilters['status']): void {
    this.filters.update((filters) => ({
      ...filters,
      status,
    }));
  }

  clearFilters(): void {
    this.filters.set({
      ...DEFAULT_PLANT_FILTERS,
    });
  }
}
