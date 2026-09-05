import { Injectable } from '@angular/core';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root',
})
export class TenantContextService {
  getTenantId(): string {
    return environment.tenantId;
  }
}
