import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

import { AdminAbility } from '../../models/admin-ability.model';
import { AdminUser } from '../../models/admin-user.model';

interface PermissionDefinition {
  name: string;
  label: string;
}

interface PermissionGroup {
  name: string;
  permissions: PermissionDefinition[];
}

@Component({
  selector: 'app-role-permissions',
  standalone: true,
  templateUrl: './role-permissions.component.html',
  styleUrl: './role-permissions.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RolePermissionsComponent {
  readonly user = input.required<AdminUser>();
  readonly abilities = input<AdminAbility[]>([]);

  readonly permissionGroups: PermissionGroup[] = [
    {
      name: 'Telemetry',
      permissions: [
        {
          name: 'telemetry:read',
          label: 'Read telemetry',
        },
        {
          name: 'telemetry:write',
          label: 'Write telemetry',
        },
      ],
    },
    {
      name: 'Alerts',
      permissions: [
        {
          name: 'alerts:read',
          label: 'Read alerts',
        },
        {
          name: 'alerts:acknowledge',
          label: 'Acknowledge alerts',
        },
        {
          name: 'alerts:resolve',
          label: 'Resolve alerts',
        },
      ],
    },
    {
      name: 'Dead Letter Queue',
      permissions: [
        {
          name: 'dlq:read',
          label: 'Read dead letter queue',
        },
        {
          name: 'dlq:replay',
          label: 'Replay dead letter events',
        },
      ],
    },
  ];

  readonly grantedAbilityNames = computed(
    () => new Set(this.abilities().map((ability) => ability.name)),
  );

  readonly grantedPermissionCount = computed(() =>
    this.permissionGroups.reduce(
      (count, group) =>
        count +
        group.permissions.filter((permission) => this.grantedAbilityNames().has(permission.name))
          .length,
      0,
    ),
  );

  readonly totalPermissionCount = computed(() =>
    this.permissionGroups.reduce((count, group) => count + group.permissions.length, 0),
  );

  hasPermission(permissionName: string): boolean {
    return this.grantedAbilityNames().has(permissionName);
  }
}
