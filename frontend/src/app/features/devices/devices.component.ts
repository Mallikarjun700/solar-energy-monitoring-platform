import {
  ChangeDetectionStrategy,
  Component,
  DestroyRef,
  computed,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

import { PageHeader } from '../../shared/components/page-header/page-header';
import { LoadingState } from '../../shared/components/loading-state/loading-state';
import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { ErrorState } from '../../shared/components/error-state/error-state';

import { Device } from './models/device.model';
import { DEFAULT_DEVICE_FILTERS, DeviceFilters } from './models/device-filters.model';
import { DevicesService } from './services/devices.service';

import { DeviceFiltersComponent } from './device-filters/device-filters.component';
import { DeviceListComponent } from './components/device-list/device-list.component';

@Component({
  selector: 'app-devices',
  standalone: true,
  imports: [
    PageHeader,
    LoadingState,
    EmptyState,
    ErrorState,
    DeviceFiltersComponent,
    DeviceListComponent,
  ],
  templateUrl: './devices.component.html',
  styleUrl: './devices.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DevicesComponent {
  private readonly devicesService = inject(DevicesService);
  private readonly destroyRef = inject(DestroyRef);

  readonly devices = signal<Device[]>([]);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  readonly filters = signal<DeviceFilters>({
    ...DEFAULT_DEVICE_FILTERS,
  });

  readonly filteredDevices = computed(() => {
    const devices = this.devices();
    const { search, status, assetId } = this.filters();

    const normalizedSearch = search.trim().toLowerCase();
    const normalizedStatus = status.trim().toLowerCase();

    return devices.filter((device) => {
      const matchesSearch =
        normalizedSearch.length === 0 ||
        device.deviceType.toLowerCase().includes(normalizedSearch) ||
        device.serialNumber.toLowerCase().includes(normalizedSearch) ||
        device.status.toLowerCase().includes(normalizedSearch);

      const matchesStatus =
        normalizedStatus.length === 0 || device.status.toLowerCase() === normalizedStatus;

      const matchesAsset = assetId === null || device.assetId === assetId;

      return matchesSearch && matchesStatus && matchesAsset;
    });
  });

  readonly hasActiveFilters = computed(() => {
    const filters = this.filters();

    return (
      filters.search.trim().length > 0 ||
      filters.status.trim().length > 0 ||
      filters.assetId !== null
    );
  });

  readonly resultCount = computed(() => this.filteredDevices().length);

  constructor() {
    this.loadDevices();
  }

  loadDevices(): void {
    this.loading.set(true);
    this.error.set(null);

    const filters = this.filters();

    this.devicesService
      .getDevices({
        assetId: filters.assetId ?? undefined,
        status: filters.status || undefined,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (devices) => {
          this.devices.set(devices);
          this.loading.set(false);
        },
        error: (error: unknown) => {
          this.devices.set([]);
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

          this.error.set('Unable to load devices.');
        },
      });
  }

  updateSearch(search: string): void {
    this.filters.update((current) => ({
      ...current,
      search,
    }));
  }

  updateStatus(status: string): void {
    this.filters.update((current) => ({
      ...current,
      status,
    }));

    this.loadDevices();
  }

  updateAssetId(assetId: number | null): void {
    this.filters.update((current) => ({
      ...current,
      assetId,
    }));

    this.loadDevices();
  }

  clearFilters(): void {
    this.filters.set({
      ...DEFAULT_DEVICE_FILTERS,
    });

    this.loadDevices();
  }
}
