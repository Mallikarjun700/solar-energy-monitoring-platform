import { ChangeDetectionStrategy, Component, input, output } from '@angular/core';

import { DeviceFilters } from '../models/device-filters.model';

@Component({
  selector: 'app-device-filters',
  standalone: true,
  templateUrl: './device-filters.component.html',
  styleUrl: './device-filters.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DeviceFiltersComponent {
  readonly filters = input.required<DeviceFilters>();

  readonly searchChange = output<string>();
  readonly statusChange = output<string>();
  readonly assetIdChange = output<number | null>();
  readonly clear = output<void>();

  onSearchChange(value: string): void {
    this.searchChange.emit(value);
  }

  onStatusChange(value: string): void {
    this.statusChange.emit(value);
  }

  onAssetIdChange(value: string): void {
    if (value.trim() === '') {
      this.assetIdChange.emit(null);
      return;
    }

    const assetId = Number(value);

    if (Number.isInteger(assetId) && assetId > 0) {
      this.assetIdChange.emit(assetId);
    }
  }

  onClear(): void {
    this.clear.emit();
  }
}
