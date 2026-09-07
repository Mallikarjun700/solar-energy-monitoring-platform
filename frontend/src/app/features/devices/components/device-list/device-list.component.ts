import { ChangeDetectionStrategy, Component, input } from '@angular/core';

import { Device } from '../../models/device.model';
import { DeviceCardComponent } from '../device-card/device-card';

@Component({
  selector: 'app-device-list',
  standalone: true,
  imports: [DeviceCardComponent],
  templateUrl: './device-list.component.html',
  styleUrl: './device-list.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DeviceListComponent {
  readonly devices = input.required<Device[]>();
}
