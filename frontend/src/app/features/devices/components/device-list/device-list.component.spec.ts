import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { Device } from '../../models/device.model';

import { DeviceListComponent } from './device-list.component';

describe('DeviceListComponent', () => {
  let fixture: ComponentFixture<DeviceListComponent>;
  let component: DeviceListComponent;

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
  ];

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [DeviceListComponent],
      providers: [provideRouter([])],
    });

    fixture = TestBed.createComponent(DeviceListComponent);

    fixture.componentRef.setInput('devices', devices);

    component = fixture.componentInstance;

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render one card for each device', () => {
    const element = fixture.nativeElement as HTMLElement;

    const cards = element.querySelectorAll('app-device-card');

    expect(cards.length).toBe(2);
  });

  it('should pass each device to the card component', () => {
    const cards = fixture.debugElement.queryAll(
      (node) => node.nativeElement.tagName === 'APP-DEVICE-CARD',
    );

    expect(cards.length).toBe(2);

    expect(cards[0].componentInstance.device()).toEqual(devices[0]);

    expect(cards[1].componentInstance.device()).toEqual(devices[1]);
  });

  it('should track devices by id', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelectorAll('.device-list-item').length).toBe(2);
  });

  it('should render the device list as a semantic list', () => {
    const element = fixture.nativeElement as HTMLElement;

    const list = element.querySelector('[role="list"]');

    expect(list).not.toBeNull();
    expect(list?.getAttribute('aria-label')).toBe('Device list');
  });

  it('should render an empty state when no devices exist', () => {
    fixture.componentRef.setInput('devices', []);

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelector('.device-grid')).toBeNull();

    expect(element.querySelector('.empty-list')).not.toBeNull();

    expect(element.textContent).toContain('No devices to display.');
  });

  it('should render no cards when the list is empty', () => {
    fixture.componentRef.setInput('devices', []);

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelectorAll('app-device-card').length).toBe(0);
  });

  it('should render each device card as a list item', () => {
    const element = fixture.nativeElement as HTMLElement;

    const items = element.querySelectorAll('[role="listitem"]');

    expect(items.length).toBe(2);

    for (const item of Array.from(items)) {
      expect(item.querySelector('app-device-card')).not.toBeNull();
    }
  });
});
