import { AdminAbility } from './admin-ability.model';
import { AdminUser } from './admin-user.model';

export interface AdministrationProfile {
  user: AdminUser;
  abilities: AdminAbility[];
}
