import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of, throwError } from 'rxjs';

import { Device } from './models/device.model';
import { DevicesComponent } from './devices.component';
import { DevicesService } from './services/devices.service';

describe('DevicesComponent', () => {
  let fixture: ComponentFixture<DevicesComponent>;
  let component: DevicesComponent;

  let devicesService: {
    getDevices: ReturnType<typeof vi.fn>;
  };

  const devices: Device[] = [
    {
      id: 1,
      assetId: 10,
      deviceType: 'inverter',
      serialNumber: 'INV-001',
      status: 'ACTIVE',
      lastSeenAt: '2026-09-06T06:30:00.000Z',
      createdAt: '2026-08-20T10:00:00.000Z',
      updatedAt: '2026-09-06T06:30:00.000Z',
    },
    {
      id: 2,
      assetId: 20,
      deviceType: 'meter',
      serialNumber: 'MTR-001',
      status: 'INACTIVE',
      lastSeenAt: null,
      createdAt: '2026-08-21T10:00:00.000Z',
      updatedAt: '2026-08-21T10:00:00.000Z',
    },
    {
      id: 3,
      assetId: 10,
      deviceType: 'sensor',
      serialNumber: 'SNS-001',
      status: 'ACTIVE',
      lastSeenAt: '2026-09-06T06:00:00.000Z',
      createdAt: '2026-08-22T10:00:00.000Z',
      updatedAt: '2026-09-06T06:00:00.000Z',
    },
  ];

  beforeEach(() => {
    devicesService = {
      getDevices: vi.fn().mockReturnValue(of(devices)),
    };

    TestBed.configureTestingModule({
      imports: [DevicesComponent],
      providers: [
        provideRouter([]),
        {
          provide: DevicesService,
          useValue: devicesService,
        },
      ],
    });

    fixture = TestBed.createComponent(DevicesComponent);

    component = fixture.componentInstance;

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should load devices on initialization', () => {
    expect(devicesService.getDevices).toHaveBeenCalledTimes(1);

    expect(devicesService.getDevices).toHaveBeenCalledWith({
      assetId: undefined,
      status: undefined,
    });

    expect(component.devices()).toEqual(devices);

    expect(component.loading()).toBe(false);
  });

  it('should render a card for every visible device', () => {
    const element = fixture.nativeElement as HTMLElement;

    const cards = element.querySelectorAll('app-device-card');

    expect(cards.length).toBe(3);
  });

  it('should filter cards when search changes', () => {
    component.updateSearch('INV-001');

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    const cards = element.querySelectorAll('app-device-card');

    expect(cards.length).toBe(1);
    expect(cards[0].textContent).toContain('INV-001');
  });

  it('should search by device type', () => {
    component.updateSearch('meter');

    fixture.detectChanges();

    expect(component.filteredDevices()).toEqual([devices[1]]);
  });

  it('should search by serial number', () => {
    component.updateSearch('SNS-001');

    expect(component.filteredDevices()).toEqual([devices[2]]);
  });

  it('should search by status text', () => {
    component.updateSearch('inactive');

    expect(component.filteredDevices()).toEqual([devices[1]]);
  });

  it('should perform search filtering without an API call', () => {
    devicesService.getDevices.mockClear();

    component.updateSearch('INV-001');

    expect(devicesService.getDevices).not.toHaveBeenCalled();

    expect(component.filteredDevices()).toEqual([devices[0]]);
  });

  it('should request the backend when status changes', () => {
    devicesService.getDevices.mockClear();

    component.updateStatus('ACTIVE');

    expect(devicesService.getDevices).toHaveBeenCalledTimes(1);

    expect(devicesService.getDevices).toHaveBeenCalledWith({
      assetId: undefined,
      status: 'ACTIVE',
    });
  });

  it('should request the backend when asset changes', () => {
    devicesService.getDevices.mockClear();

    component.updateAssetId(10);

    expect(devicesService.getDevices).toHaveBeenCalledTimes(1);

    expect(devicesService.getDevices).toHaveBeenCalledWith({
      assetId: 10,
      status: undefined,
    });
  });

  it('should support combined backend filters', () => {
    devicesService.getDevices.mockClear();

    component.updateStatus('ACTIVE');
    component.updateAssetId(10);

    expect(devicesService.getDevices).toHaveBeenLastCalledWith({
      assetId: 10,
      status: 'ACTIVE',
    });
  });

  it('should apply search on top of backend-filtered data', () => {
    component.updateStatus('ACTIVE');

    component.updateSearch('SNS-001');

    expect(component.filteredDevices()).toEqual([devices[2]]);
  });

  it('should update the rendered card count after search', () => {
    component.updateSearch('sensor');

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelectorAll('app-device-card').length).toBe(1);

    expect(element.textContent).toContain('1 device shown');
  });

  it('should show no matching devices when filters exclude all devices', () => {
    component.updateSearch('does-not-exist');

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelectorAll('app-device-card').length).toBe(0);

    expect(element.textContent).toContain('No matching devices');
  });

  it('should show clear filters when no devices match active filters', () => {
    component.updateSearch('does-not-exist');

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    const button = element.querySelector('.clear-filters-button') as HTMLButtonElement;

    expect(button).not.toBeNull();
    expect(button.textContent).toContain('Clear Filters');
  });

  it('should clear filters and reload devices', () => {
    component.updateSearch('INV-001');
    component.updateStatus('ACTIVE');
    component.updateAssetId(10);

    devicesService.getDevices.mockClear();

    component.clearFilters();

    expect(component.filters()).toEqual({
      search: '',
      status: '',
      assetId: null,
    });

    expect(devicesService.getDevices).toHaveBeenCalledTimes(1);

    expect(devicesService.getDevices).toHaveBeenCalledWith({
      assetId: undefined,
      status: undefined,
    });
  });

  it('should reset the rendered collection after clearing filters', () => {
    component.updateSearch('does-not-exist');

    fixture.detectChanges();

    component.clearFilters();

    fixture.detectChanges();

    expect(component.filteredDevices()).toEqual(devices);

    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelectorAll('app-device-card').length).toBe(3);
  });

  it('should expose active filters correctly', () => {
    expect(component.hasActiveFilters()).toBe(false);

    component.updateSearch('INV');

    expect(component.hasActiveFilters()).toBe(true);
  });

  it('should expose the current result count', () => {
    expect(component.resultCount()).toBe(3);

    component.updateSearch('INV');

    expect(component.resultCount()).toBe(1);
  });

  it('should handle API errors', () => {
    devicesService.getDevices.mockReturnValue(
      throwError(() => ({
        message: 'Unable to load devices.',
      })),
    );

    component.loadDevices();
    fixture.detectChanges();

    expect(component.loading()).toBe(false);

    expect(component.devices()).toEqual([]);

    expect(component.error()).toBe('Unable to load devices.');
  });

  it('should clear an existing error when loading starts', () => {
    component.error.set('Previous error');

    component.loadDevices();

    expect(component.error()).toBeNull();
  });

  it('should preserve device detail navigation through the card', () => {
    component.updateSearch('INV-001');

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    const links = element.querySelectorAll('app-device-card a');

    expect(links.length).toBe(2);

    for (const link of Array.from(links)) {
      expect(link.getAttribute('href')).toBe('/devices/1');
    }
  });

  it('should associate the card with its device heading', () => {
    const element = fixture.nativeElement as HTMLElement;

    const article = element.querySelector('article') as HTMLElement;

    const heading = element.querySelector('h3') as HTMLElement;

    expect(article.getAttribute('aria-labelledby')).toBe(`device-title-${devices[0].id}`);

    expect(heading.id).toBe(`device-title-${devices[0].id}`);
  });

  it('should provide meaningful accessible names for both detail links', () => {
    const element = fixture.nativeElement as HTMLElement;

    const firstCard = element.querySelector('.device-list-item app-device-card');
    const links = firstCard?.querySelectorAll('a') ?? [];

    expect(links.length).toBe(2);

    expect(links[0].getAttribute('aria-label')).toBe('View details for device INV-001');

    expect(links[1].getAttribute('aria-label')).toBe('View full details for device INV-001');
  });

  it('should keep status text visible instead of relying only on styling', () => {
    const element = fixture.nativeElement as HTMLElement;

    const status = element.querySelector('.status-badge');

    expect(status?.textContent?.trim()).toBe('ACTIVE');
  });
});
