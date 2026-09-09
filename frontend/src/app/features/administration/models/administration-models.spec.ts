import { AdminAbility } from './admin-ability.model';
import { AdministrationProfile } from './administration-profile.model';
import { AdminUser } from './admin-user.model';

describe('Administration models', () => {
  it('should represent an administration user', () => {
    const user: AdminUser = {
      id: 1,
      name: 'Admin User',
      email: 'admin@example.com',
      role: 'admin',
    };

    expect(user.id).toBe(1);
    expect(user.name).toBe('Admin User');
    expect(user.email).toBe('admin@example.com');
    expect(user.role).toBe('admin');
  });

  it('should represent an ability', () => {
    const ability: AdminAbility = {
      name: 'telemetry:read',
      label: 'Telemetry Read',
    };

    expect(ability.name).toBe('telemetry:read');
    expect(ability.label).toBe('Telemetry Read');
  });

  it('should represent an administration profile', () => {
    const profile: AdministrationProfile = {
      user: {
        id: 1,
        name: 'Admin User',
        email: 'admin@example.com',
        role: 'admin',
      },
      abilities: [
        {
          name: 'telemetry:read',
          label: 'Telemetry Read',
        },
      ],
    };

    expect(profile.user.role).toBe('admin');
    expect(profile.abilities).toHaveLength(1);
  });
});
