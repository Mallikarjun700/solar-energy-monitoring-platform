import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TenantAccessContextComponent } from './tenant-access-context.component';

describe('TenantAccessContextComponent', () => {
  let fixture: ComponentFixture<TenantAccessContextComponent>;
  let component: TenantAccessContextComponent;

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
      imports: [TenantAccessContextComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(TenantAccessContextComponent);
    component = fixture.componentInstance;

    fixture.componentRef.setInput('tenantId', '11111111-1111-4111-8111-111111111111');
    fixture.componentRef.setInput('user', user);
    fixture.componentRef.setInput('abilities', abilities);

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render the tenant ID', () => {
    expect(fixture.nativeElement.textContent).toContain('11111111-1111-4111-8111-111111111111');
  });

  it('should render the authenticated user', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Admin User');
    expect(text).toContain('admin');
  });

  it('should calculate the ability count', () => {
    expect(component.abilityCount()).toBe(3);
  });

  it('should render the ability count', () => {
    expect(fixture.nativeElement.textContent).toContain('3');
    expect(fixture.nativeElement.textContent).toContain('abilities');
  });

  it('should identify the current access scope', () => {
    expect(component.accessScope()).toBe('Current authenticated tenant context');
  });

  it('should identify an empty tenant context', () => {
    fixture.componentRef.setInput('tenantId', '');
    fixture.detectChanges();

    expect(component.accessScope()).toBe('No tenant context configured');
  });

  it('should expose an accessible section heading', () => {
    const heading = fixture.nativeElement.querySelector('#tenant-context-heading');

    expect(heading?.textContent?.trim()).toBe('Tenant / Access Context');
  });
});
