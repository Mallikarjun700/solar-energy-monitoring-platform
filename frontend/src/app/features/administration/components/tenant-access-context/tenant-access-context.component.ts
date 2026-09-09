import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

import { AdminAbility } from '../../models/admin-ability.model';
import { AdminUser } from '../../models/admin-user.model';

@Component({
  selector: 'app-tenant-access-context',
  standalone: true,
  templateUrl: './tenant-access-context.component.html',
  styleUrl: './tenant-access-context.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TenantAccessContextComponent {
  readonly tenantId = input.required<string>();
  readonly user = input.required<AdminUser>();
  readonly abilities = input<AdminAbility[]>([]);

  readonly abilityCount = computed(() => this.abilities().length);

  readonly accessScope = computed(() =>
    this.tenantId() ? 'Current authenticated tenant context' : 'No tenant context configured',
  );
}
