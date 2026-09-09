import { ChangeDetectionStrategy, Component, OnInit, inject, signal } from '@angular/core';

import { AdministrationService } from './services/administration.service';
import { AdministrationProfile } from './models/administration-profile.model';
import { UserManagementComponent } from './components/user-management/user-management.component';
import { RolePermissionsComponent } from './components/role-permissions/role-permissions.component';
import { TenantContextService } from '../../core/tenant/tenant-context.service';
import { TenantAccessContextComponent } from './components/tenant-access-context/tenant-access-context.component';
import { AuditInformationComponent } from './components/audit-information/audit-information.component';

@Component({
  selector: 'app-administration',
  imports: [
    UserManagementComponent,
    RolePermissionsComponent,
    TenantAccessContextComponent,
    AuditInformationComponent,
  ],
  standalone: true,
  templateUrl: './administration.component.html',
  styleUrl: './administration.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdministrationComponent implements OnInit {
  private readonly administrationService = inject(AdministrationService);
  private readonly tenantContext = inject(TenantContextService);

  readonly tenantId = this.tenantContext.getTenantId();

  readonly profile = signal<AdministrationProfile | null>(null);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  ngOnInit(): void {
    this.loadProfile();
  }

  loadProfile(): void {
    this.loading.set(true);
    this.error.set(null);

    this.administrationService.getProfile().subscribe({
      next: (profile) => {
        this.profile.set(profile);
        this.loading.set(false);
      },
      error: () => {
        this.profile.set(null);
        this.loading.set(false);
        this.error.set('Unable to load your administration profile. Please try again.');
      },
    });
  }

  retry(): void {
    this.loadProfile();
  }

  formatAbilityLabel(label: string): string {
    return label;
  }
}
