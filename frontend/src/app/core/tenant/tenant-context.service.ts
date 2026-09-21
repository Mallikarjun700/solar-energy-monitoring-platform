import { Injectable, inject } from '@angular/core';

import { AuthStateService } from '../services/auth/auth-state.service';

@Injectable({
  providedIn: 'root',
})
export class TenantContextService {
  private readonly authState = inject(AuthStateService);

  getTenantId(): string {
    const tenantId = this.authState.user()?.tenant_id;

    if (!tenantId) {
      throw new Error('Authenticated tenant context is unavailable.');
    }

    return tenantId;
  }
}
