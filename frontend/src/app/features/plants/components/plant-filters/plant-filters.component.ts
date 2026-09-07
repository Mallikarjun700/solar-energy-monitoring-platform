import { ChangeDetectionStrategy, Component, input, output } from '@angular/core';
import { PlantStatusFilters } from '../../models/plant-filters.model';

@Component({
  imports: [],
  standalone: true,
  selector: 'app-plant-filters',
  styleUrl: './plant-filters.component.scss',
  templateUrl: './plant-filters.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PlantFiltersComponent {
  readonly filters = input.required<PlantStatusFilters>();

  readonly searchChange = output<string>();
  readonly statusChange = output<PlantStatusFilters['status']>();
  readonly clear = output<void>();

  onSearchChange(value: string): void {
    this.searchChange.emit(value);
  }

  onStatusChange(value: string): void {
    if (value === 'ALL' || value === 'ACTIVE' || value === 'INACTIVE' || value === 'MAINTENANCE') {
      this.statusChange.emit(value);
    }
  }

  onClear(): void {
    this.clear.emit();
  }
}
