import { ChangeDetectionStrategy, Component, input } from '@angular/core';

import { AdminAbility } from '../../models/admin-ability.model';
import { AdminUser } from '../../models/admin-user.model';

@Component({
  selector: 'app-user-management',
  standalone: true,
  templateUrl: './user-management.component.html',
  styleUrl: './user-management.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UserManagementComponent {
  readonly user = input.required<AdminUser>();
  readonly abilities = input<AdminAbility[]>([]);
}
