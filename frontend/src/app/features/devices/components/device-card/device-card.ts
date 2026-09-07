import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { RouterLink } from '@angular/router';

import { Device } from '../../models/device.model';

@Component({
  selector: 'app-device-card',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './device-card.html',
  styleUrl: './device-card.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DeviceCardComponent {
  readonly device = input.required<Device>();

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

  getStatusClass(status: string): string {
    return status.trim().toLowerCase().replace(/\s+/g, '-');
  }
}
