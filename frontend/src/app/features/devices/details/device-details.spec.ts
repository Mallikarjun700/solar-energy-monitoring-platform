import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { of, throwError } from 'rxjs';

import { Device } from '../models/device.model';
import { DevicesService } from '../services/devices.service';

import { DeviceDetailsComponent } from './device-details.component';

describe('DeviceDetailsComponent', () => {
  let fixture: ComponentFixture<DeviceDetailsComponent>;
  let component: DeviceDetailsComponent;

  let devicesService: {
    getDevice: ReturnType<typeof vi.fn>;
  };

  const device: Device = {
    id: 1,
    assetId: 10,
    deviceType: 'inverter',
    serialNumber: 'INV-001',
    status: 'ACTIVE',
    lastSeenAt: '2026-09-06T06:30:00.000Z',
    createdAt: '2026-08-20T10:00:00.000Z',
    updatedAt: '2026-09-06T06:30:00.000Z',
  };

  beforeEach(() => {
    devicesService = {
      getDevice: vi.fn().mockReturnValue(of(device)),
    };

    TestBed.configureTestingModule({
      imports: [DeviceDetailsComponent],
      providers: [
        provideRouter([]),
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: convertToParamMap({
                id: '1',
              }),
            },
          },
        },
        {
          provide: DevicesService,
          useValue: devicesService,
        },
      ],
    });

    fixture = TestBed.createComponent(DeviceDetailsComponent);

    component = fixture.componentInstance;

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should load the device using the route id', () => {
    expect(devicesService.getDevice).toHaveBeenCalledTimes(1);

    expect(devicesService.getDevice).toHaveBeenCalledWith(1);

    expect(component.device()).toEqual(device);
  });

  it('should render the device serial number', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelector('h1')?.textContent).toContain('INV-001');
  });

  it('should render the device type', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelector('.eyebrow')?.textContent).toContain('inverter');
  });

  it('should render the device status', () => {
    const element = fixture.nativeElement as HTMLElement;

    const badges = element.querySelectorAll('.status-badge');

    expect(badges.length).toBe(1);
    expect(badges[0].textContent?.trim()).toBe('ACTIVE');
  });

  it('should render the device id', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Device ID');

    expect(element.textContent).toContain('1');
  });

  it('should render the asset id', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Asset ID');

    expect(element.textContent).toContain('10');
  });

  it('should render the serial number in the information section', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Serial Number');

    expect(element.textContent).toContain('INV-001');
  });

  it('should render the last seen timestamp', () => {
    const element = fixture.nativeElement as HTMLElement;

    const lastSeen = element.querySelector(
      '[aria-labelledby="device-information-title"] time',
    ) as HTMLTimeElement;

    expect(lastSeen).not.toBeNull();

    expect(lastSeen.getAttribute('datetime')).toBe(device.lastSeenAt);
  });

  it('should render created and updated timestamps', () => {
    const element = fixture.nativeElement as HTMLElement;

    const times = element.querySelectorAll('time');

    expect(
      Array.from(times).some((time) => time.getAttribute('datetime') === device.createdAt),
    ).toBe(true);

    expect(
      Array.from(times).some((time) => time.getAttribute('datetime') === device.updatedAt),
    ).toBe(true);
  });

  it('should render a back to devices link', () => {
    const element = fixture.nativeElement as HTMLElement;

    const links = element.querySelectorAll('a');

    expect(links.length).toBe(2);

    expect(Array.from(links).every((link) => link.getAttribute('href') === '/devices')).toBe(true);
  });

  it('should render the asset relationship section', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Asset Relationship');

    expect(element.textContent).toContain('This device is associated with the asset');
  });

  it('should show Never when last seen is unavailable', () => {
    component.device.set({
      ...device,
      lastSeenAt: null,
    });

    fixture.detectChanges();

    expect(component.formatLastSeen(null)).toBe('Never');
  });

  it('should show Unknown for an invalid last seen timestamp', () => {
    expect(component.formatLastSeen('invalid-date')).toBe('Unknown');
  });

  it('should show Unknown for an invalid metadata date', () => {
    expect(component.formatDate('invalid-date')).toBe('Unknown');
  });

  it('should handle API errors', () => {
    devicesService.getDevice.mockReturnValue(
      throwError(() => ({
        message: 'Unable to load device details.',
      })),
    );

    component.loadDevice();
    fixture.detectChanges();

    expect(component.loading()).toBe(false);

    expect(component.device()).toBeNull();

    expect(component.error()).toBe('Unable to load device details.');
  });

  it('should clear an existing error when loading starts', () => {
    component.error.set('Previous error');

    component.loadDevice();

    expect(component.error()).toBeNull();
  });

  it('should reject an invalid route id', () => {
    TestBed.resetTestingModule();

    const invalidService = {
      getDevice: vi.fn(),
    };

    TestBed.configureTestingModule({
      imports: [DeviceDetailsComponent],
      providers: [
        provideRouter([]),
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: convertToParamMap({
                id: 'abc',
              }),
            },
          },
        },
        {
          provide: DevicesService,
          useValue: invalidService,
        },
      ],
    });

    const invalidFixture = TestBed.createComponent(DeviceDetailsComponent);

    const invalidComponent = invalidFixture.componentInstance;

    invalidFixture.detectChanges();

    expect(invalidComponent.error()).toBe('Invalid device ID.');

    expect(invalidService.getDevice).not.toHaveBeenCalled();
  });
});
