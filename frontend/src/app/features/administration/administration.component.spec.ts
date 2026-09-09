import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { Observable, of, throwError } from 'rxjs';

import { AdministrationComponent } from './administration.component';
import { AdministrationService } from './services/administration.service';

describe('AdministrationComponent', () => {
  let fixture: ComponentFixture<AdministrationComponent>;
  let component: AdministrationComponent;
  let administrationService: {
    getProfile: ReturnType<typeof vi.fn>;
  };

  const profile = {
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
      {
        name: 'telemetry:write',
        label: 'Telemetry Write',
      },
      {
        name: 'alerts:read',
        label: 'Alerts Read',
      },
    ],
  };

  beforeEach(async () => {
    administrationService = {
      getProfile: vi.fn().mockReturnValue(of(profile)),
    };

    await TestBed.configureTestingModule({
      imports: [AdministrationComponent],
      providers: [
        {
          provide: AdministrationService,
          useValue: administrationService,
        },
        {
          provide: ActivatedRoute,
          useValue: {},
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(AdministrationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should load the administration profile on initialization', () => {
    expect(administrationService.getProfile).toHaveBeenCalledTimes(1);
    expect(component.profile()).toEqual(profile);
    expect(component.loading()).toBe(false);
  });

  it('should render the page heading', () => {
    const heading: HTMLElement | null = fixture.nativeElement.querySelector('h1');

    expect(heading?.textContent?.trim()).toBe('Administration');
  });

  it('should render the current user information', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Admin User');
    expect(text).toContain('admin@example.com');
    expect(text).toContain('admin');
    expect(text).toContain('1');
  });

  it('should render the user abilities', () => {
    const items = fixture.nativeElement.querySelectorAll('.ability-item');

    expect(items.length).toBe(3);

    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Telemetry Read');
    expect(text).toContain('Telemetry Write');
    expect(text).toContain('Alerts Read');
  });

  it('should display the ability count', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('3');
    expect(text).toContain('abilities');
  });

  it('should retry loading the profile', () => {
    administrationService.getProfile.mockReturnValue(of(profile));

    component.retry();

    expect(administrationService.getProfile).toHaveBeenCalledTimes(2);
    expect(component.profile()).toEqual(profile);
  });

  it('should display an error when loading fails', () => {
    administrationService.getProfile.mockReturnValue(throwError(() => new Error('Request failed')));

    component.loadProfile();
    fixture.detectChanges();

    expect(component.profile()).toBeNull();
    expect(component.loading()).toBe(false);
    expect(component.error()).toContain('Unable to load');

    const text = fixture.nativeElement.textContent;
    expect(text).toContain('Unable to load administration');
    expect(text).toContain('Retry');
  });

  it('should display the empty abilities state', () => {
    administrationService.getProfile.mockReturnValue(
      of({
        ...profile,
        abilities: [],
      }),
    );

    component.loadProfile();
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('No abilities assigned');
  });

  it('should show the loading state while the profile request is pending', () => {
    administrationService.getProfile.mockReturnValue(
      new Observable(() => {
        // Keep the request pending.
      }),
    );

    component.loadProfile();
    fixture.detectChanges();

    expect(component.loading()).toBe(true);

    const loadingState = fixture.nativeElement.querySelector('.loading-state');

    expect(loadingState).toBeTruthy();
    expect(loadingState.textContent).toContain('Loading administration');
  });

  it('should clear a previous error when retry starts', () => {
    administrationService.getProfile.mockReturnValue(throwError(() => new Error('Request failed')));

    component.loadProfile();
    fixture.detectChanges();

    expect(component.error()).toContain('Unable to load');

    administrationService.getProfile.mockReturnValue(of(profile));

    component.retry();
    fixture.detectChanges();

    expect(component.error()).toBeNull();
    expect(component.loading()).toBe(false);
    expect(component.profile()).toEqual(profile);
  });

  it('should expose a retry action when loading fails', () => {
    administrationService.getProfile.mockReturnValue(throwError(() => new Error('Request failed')));

    component.loadProfile();
    fixture.detectChanges();

    const retryButton = fixture.nativeElement.querySelector(
      '.error-state .secondary-button',
    ) as HTMLButtonElement | null;

    expect(retryButton).toBeTruthy();
    expect(retryButton?.textContent?.trim()).toBe('Retry');
  });

  it('should render the unexpected empty profile state', () => {
    administrationService.getProfile.mockReturnValue(of(null as never));

    component.loadProfile();
    fixture.detectChanges();

    expect(component.profile()).toBeNull();
    expect(component.error()).toBeNull();
    expect(component.loading()).toBe(false);

    expect(fixture.nativeElement.textContent).toContain('Administration information unavailable');
  });

  it('should expose accessible loading and error regions', () => {
    administrationService.getProfile.mockReturnValue(throwError(() => new Error('Request failed')));

    component.loadProfile();
    fixture.detectChanges();

    const errorState = fixture.nativeElement.querySelector('.error-state') as HTMLElement | null;

    expect(errorState?.getAttribute('role')).toBe('alert');

    administrationService.getProfile.mockReturnValue(
      new Observable(() => {
        // Keep the request pending.
      }),
    );

    component.loadProfile();
    fixture.detectChanges();

    const loadingState = fixture.nativeElement.querySelector(
      '.loading-state',
    ) as HTMLElement | null;

    expect(loadingState?.getAttribute('aria-live')).toBe('polite');
    expect(loadingState?.getAttribute('aria-busy')).toBe('true');
  });
});
