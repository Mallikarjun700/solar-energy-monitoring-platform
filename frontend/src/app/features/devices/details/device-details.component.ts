import { ChangeDetectionStrategy, Component, DestroyRef, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

import { ErrorState } from '../../../shared/components/error-state/error-state';
import { LoadingState } from '../../../shared/components/loading-state/loading-state';

import { Device } from '../models/device.model';
import { DevicesService } from '../services/devices.service';

@Component({
  selector: 'app-device-details',
  standalone: true,
  imports: [RouterLink, LoadingState, ErrorState],
  templateUrl: './device-details.html',
  styleUrl: './device-details.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DeviceDetailsComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly devicesService = inject(DevicesService);
  private readonly destroyRef = inject(DestroyRef);

  readonly device = signal<Device | null>(null);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  constructor() {
    this.loadDevice();
  }

  loadDevice(): void {
    const idParam = this.route.snapshot.paramMap.get('id');

    if (!idParam) {
      this.device.set(null);
      this.loading.set(false);
      this.error.set('Device ID is required.');
      return;
    }

    const id = Number(idParam);

    if (!Number.isInteger(id) || id <= 0) {
      this.device.set(null);
      this.loading.set(false);
      this.error.set('Invalid device ID.');
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    this.devicesService
      .getDevice(id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (device) => {
          this.device.set(device);
          this.loading.set(false);
        },
        error: (error: unknown) => {
          this.device.set(null);
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

          this.error.set('Unable to load device details.');
        },
      });
  }

  formatLastSeen(lastSeenAt: string | null): string {
    if (!lastSeenAt) {
      return 'Never';
    }

    const date = new Date(lastSeenAt);

    if (Number.isNaN(date.getTime())) {
      return 'Unknown';
    }

    return date.toLocaleString();
  }

  formatDate(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
      return 'Unknown';
    }

    return date.toLocaleString();
  }

  getStatusClass(status: string): string {
    return status.trim().toLowerCase().replace(/\s+/g, '-');
  }
}
