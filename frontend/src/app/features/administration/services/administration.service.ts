import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { ApiService } from '../../../core/services/api.service';
import { AdminAbility } from '../models/admin-ability.model';
import { AdministrationProfile } from '../models/administration-profile.model';
import { AdministrationApiResponse } from '../models/administration-response.model';

@Injectable({
  providedIn: 'root',
})
export class AdministrationService {
  private readonly api = inject(ApiService);

  private readonly profileEndpoint = '/auth/me';

  getProfile(): Observable<AdministrationProfile> {
    return this.api.get<AdministrationApiResponse>(this.profileEndpoint).pipe(
      map((response) => ({
        user: response.data.user,
        abilities: response.data.abilities.map((ability) => this.mapAbility(ability)),
      })),
    );
  }

  private mapAbility(name: string): AdminAbility {
    return {
      name,
      label: this.formatAbilityLabel(name),
    };
  }

  private formatAbilityLabel(name: string): string {
    const [resource, action] = name.split(':');

    if (!resource || !action) {
      return name;
    }

    return `${this.capitalize(resource)} ${this.capitalize(action)}`;
  }

  private capitalize(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
  }
}
