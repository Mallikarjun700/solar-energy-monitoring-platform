import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { Plant } from '../../models/plant.model';
import { PlantCardComponent } from '../plant-card/plant-card.component';

@Component({
  imports: [PlantCardComponent],
  standalone: true,
  selector: 'app-plant-list',
  styleUrl: './plant-list.component.scss',
  templateUrl: './plant-list.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PlantListComponent {
  readonly plants = input.required<Plant[]>();
}
