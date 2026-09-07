import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { DecimalPipe } from '@angular/common';
import { Plant } from '../../models/plant.model';
import { RouterLink } from '@angular/router';

@Component({
  standalone: true,
  imports: [DecimalPipe, RouterLink],
  selector: 'app-plant-card',
  styleUrl: './plant-card.component.scss',
  templateUrl: './plant-card.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PlantCardComponent {
  readonly plant = input.required<Plant>();
}
