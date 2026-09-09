import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UserManagementComponent } from './user-management.component';

describe('UserManagementComponent', () => {
  let fixture: ComponentFixture<UserManagementComponent>;
  let component: UserManagementComponent;

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
  ];

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [UserManagementComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(UserManagementComponent);
    component = fixture.componentInstance;

    fixture.componentRef.setInput('user', user);
    fixture.componentRef.setInput('abilities', abilities);

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render the user details', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Admin User');
    expect(text).toContain('admin@example.com');
    expect(text).toContain('1');
    expect(text).toContain('admin');
  });

  it('should render the permissions', () => {
    const items = fixture.nativeElement.querySelectorAll('.permission-item');

    expect(items.length).toBe(3);

    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Telemetry Read');
    expect(text).toContain('Telemetry Write');
    expect(text).toContain('Alerts Read');
  });

  it('should display the permission count', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('3');
    expect(text).toContain('permissions');
  });

  it('should render an empty state when there are no permissions', () => {
    fixture.componentRef.setInput('abilities', []);
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('No permissions assigned');
  });

  it('should expose accessible section heading', () => {
    const section = fixture.nativeElement.querySelector('.user-management');
    const heading = fixture.nativeElement.querySelector('#user-management-heading');

    expect(section).toBeTruthy();
    expect(heading?.textContent?.trim()).toBe('User Management');
  });
});
