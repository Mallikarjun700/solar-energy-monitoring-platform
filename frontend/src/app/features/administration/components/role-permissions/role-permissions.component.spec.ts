import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RolePermissionsComponent } from './role-permissions.component';

describe('RolePermissionsComponent', () => {
  let fixture: ComponentFixture<RolePermissionsComponent>;
  let component: RolePermissionsComponent;

  const user = {
    id: 1,
    name: 'Admin User',
    email: 'admin@example.com',
    role: 'admin',
  };

  const abilities = [
    {
      name: 'telemetry:read',
      label: 'Telemetry Read',
    },
    {
      name: 'telemetry:write',
      label: 'Telemetry Write',
    },
    {
      name: 'alerts:read',
      label: 'Alerts Read',
    },
    {
      name: 'alerts:acknowledge',
      label: 'Alerts Acknowledge',
    },
    {
      name: 'alerts:resolve',
      label: 'Alerts Resolve',
    },
    {
      name: 'dlq:read',
      label: 'DLQ Read',
    },
    {
      name: 'dlq:replay',
      label: 'DLQ Replay',
    },
  ];

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [RolePermissionsComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(RolePermissionsComponent);
    component = fixture.componentInstance;

    fixture.componentRef.setInput('user', user);
    fixture.componentRef.setInput('abilities', abilities);

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render the current role', () => {
    expect(fixture.nativeElement.textContent).toContain('admin');
  });

  it('should define all supported permission groups', () => {
    expect(component.permissionGroups.map((group) => group.name)).toEqual([
      'Telemetry',
      'Alerts',
      'Dead Letter Queue',
    ]);
  });

  it('should recognize granted permissions', () => {
    expect(component.hasPermission('telemetry:read')).toBe(true);
    expect(component.hasPermission('dlq:replay')).toBe(true);
  });

  it('should recognize permissions that are not granted', () => {
    fixture.componentRef.setInput('abilities', [{name: 'telemetry:read',label: 'Telemetry Read',},]);
    fixture.detectChanges();

    expect(component.hasPermission('telemetry:read')).toBe(true);
    expect(component.hasPermission('telemetry:write')).toBe(false);
    expect(component.hasPermission('dlq:replay')).toBe(false);
  });

  it('should calculate the granted permission count', () => {
    expect(component.grantedPermissionCount()).toBe(7);
    expect(component.totalPermissionCount()).toBe(7);
  });

  it('should calculate a partial permission set', () => {
    fixture.componentRef.setInput('abilities', [
      {
        name: 'telemetry:read',
        label: 'Telemetry Read',
      },
      {
        name: 'alerts:read',
        label: 'Alerts Read',
      },
    ]);

    fixture.detectChanges();

    expect(component.grantedPermissionCount()).toBe(2);
  });

  it('should render granted and not-granted states', () => {
    fixture.componentRef.setInput('abilities', [
      {
        name: 'telemetry:read',
        label: 'Telemetry Read',
      },
    ]);

    fixture.detectChanges();

    const granted = fixture.nativeElement.querySelectorAll('.permission-granted');
    const denied = fixture.nativeElement.querySelectorAll('.permission-denied');

    expect(granted.length).toBe(1);
    expect(denied.length).toBe(6);
  });

  it('should render permission names', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('telemetry:read');
    expect(text).toContain('telemetry:write');
    expect(text).toContain('alerts:read');
    expect(text).toContain('alerts:acknowledge');
    expect(text).toContain('alerts:resolve');
    expect(text).toContain('dlq:read');
    expect(text).toContain('dlq:replay');
  });
});
