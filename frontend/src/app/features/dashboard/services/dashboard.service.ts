import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { ApiService } from '../../../core/services/api.service';
import { TenantContextService } from '../../../core/tenant/tenant-context.service';
import { DashboardViewModel } from '../models/dashboard-view.model';

interface DashboardApiResponse {
  status: string;
  message: string;
  data: DashboardViewModel;
  correlation_id?: string;
}

@Injectable({
  providedIn: 'root',
})
export class DashboardService {
  private readonly api = inject(ApiService);
  private readonly tenantContext = inject(TenantContextService);

  getDashboard(): Observable<DashboardViewModel> {
    const tenantId = this.tenantContext.getTenantId();

    return this.api
      .get<DashboardApiResponse>('/dashboard', {
        tenant_id: tenantId,
      })
      .pipe(map((response) => response.data));
  }
}
