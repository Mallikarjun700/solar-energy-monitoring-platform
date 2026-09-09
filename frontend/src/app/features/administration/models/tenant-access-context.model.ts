import { AdminAbility } from './admin-ability.model';
import { AdminUser } from './admin-user.model';

export interface TenantAccessContext {
  tenantId: string;
  user: AdminUser;
  abilities: AdminAbility[];
}
