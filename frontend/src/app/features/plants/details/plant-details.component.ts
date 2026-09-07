import { ChangeDetectionStrategy, Component, DestroyRef, inject, signal } from '@angular/core';
import { Location } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { finalize } from 'rxjs';

import { ApiErrorService } from '../../../core/services/api-error.service';
import { EmptyState } from '../../../shared/components/empty-state/empty-state';
import { ErrorState } from '../../../shared/components/error-state/error-state';
import { LoadingState } from '../../../shared/components/loading-state/loading-state';
import { PageHeader } from '../../../shared/components/page-header/page-header';
import { PlantsService } from '../services/plants.service';
import { Plant } from '../models/plant.model';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

@Component({
  selector: 'app-plant-details',
  standalone: true,
  imports: [RouterLink, PageHeader, LoadingState, ErrorState],
  templateUrl: './plant-details.component.html',
  styleUrl: './plant-details.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PlantDetailsComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly location = inject(Location);
  private readonly plantsService = inject(PlantsService);
  private readonly destroyRef = inject(DestroyRef);

  readonly plant = signal<Plant | null>(null);
  readonly loading = signal(true);
  readonly error = signal<string | null>(null);

  constructor() {
    this.loadPlant();
  }

  loadPlant(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    if (!Number.isInteger(id) || id <= 0) {
      this.loading.set(false);
      this.error.set('The requested plant could not be identified.');
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    this.plantsService
      .getPlant(id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (plant) => {
          this.plant.set(plant);
          this.loading.set(false);
        },
        error: (error: unknown) => {
          this.plant.set(null);
          this.loading.set(false);

          if (
            error &&
            typeof error === 'object' &&
            'message' in error &&
            typeof error.message === 'string'
          ) {
            this.error.set(error.message);
            return;
          }

          this.error.set('Unable to load plant details.');
        },
      });
  }

  goBack(): void {
    this.location.back();
  }

  formatCapacity(capacityKw: number | null): string {
    if (capacityKw === null) {
      return 'Not available';
    }

    return `${capacityKw.toLocaleString('en-IN', {
      maximumFractionDigits: 2,
    })} kW`;
  }

  formatDate(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
      return 'Not available';
    }

    return date.toLocaleString('en-IN', {
      dateStyle: 'medium',
      timeStyle: 'short',
    });
  }
}
