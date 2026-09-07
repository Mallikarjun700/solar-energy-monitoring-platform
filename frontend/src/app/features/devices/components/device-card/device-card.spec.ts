import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { Device } from '../../models/device.model';

import { DeviceCardComponent } from './device-card';

describe('DeviceCardComponent', () => {
  let fixture: ComponentFixture<DeviceCardComponent>;
  let component: DeviceCardComponent;

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
    TestBed.configureTestingModule({
      imports: [DeviceCardComponent],
      providers: [provideRouter([])],
    });

    fixture = TestBed.createComponent(DeviceCardComponent);

    fixture.componentRef.setInput('device', device);

    component = fixture.componentInstance;

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render the device type', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelector('.device-type')?.textContent).toContain('inverter');
  });

  it('should render the serial number', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelector('h3')?.textContent).toContain('INV-001');
  });

  it('should render the device status', () => {
    const element = fixture.nativeElement as HTMLElement;

    const badge = element.querySelector('.status-badge') as HTMLElement;

    expect(badge).not.toBeNull();
    expect(badge.textContent?.trim()).toBe('ACTIVE');
    expect(badge.classList.contains('active')).toBe(true);
  });

  it('should render the asset id', () => {
    const element = fixture.nativeElement as HTMLElement;

    const details = element.querySelector('.device-details') as HTMLElement;

    expect(details.textContent).toContain('10');
  });

  it('should render the last seen timestamp', () => {
    const element = fixture.nativeElement as HTMLElement;

    const time = element.querySelector('time') as HTMLTimeElement;

    expect(time).not.toBeNull();
    expect(time.getAttribute('datetime')).toBe(device.lastSeenAt);
  });

  it('should display Never when last seen is unavailable', () => {
    fixture.componentRef.setInput('device', {
      ...device,
      lastSeenAt: null,
    });

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelector('time')?.textContent).toContain('Never');
  });

  it('should display Unknown for an invalid last seen timestamp', () => {
    fixture.componentRef.setInput('device', {
      ...device,
      lastSeenAt: 'invalid-date',
    });

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelector('time')?.textContent).toContain('Unknown');
  });

  it('should navigate to the device details route', () => {
    const element = fixture.nativeElement as HTMLElement;

    const links = element.querySelectorAll('a');

    expect(links.length).toBe(2);

    for (const link of Array.from(links)) {
      expect(link.getAttribute('href')).toBe('/devices/1');
    }
  });

  it('should provide an accessible label for the serial number link', () => {
    const element = fixture.nativeElement as HTMLElement;

    const link = element.querySelector('h3 a') as HTMLAnchorElement;

    expect(link.getAttribute('aria-label')).toBe('View details for device INV-001');
  });

  it('should expose the status through an accessible label', () => {
    const element = fixture.nativeElement as HTMLElement;

    const badge = element.querySelector('.status-badge') as HTMLElement;

    expect(badge.getAttribute('aria-label')).toBe('Status: ACTIVE');
  });

  it('should support arbitrary backend status values', () => {
    fixture.componentRef.setInput('device', {
      ...device,
      status: 'DEGRADED',
    });

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    const badge = element.querySelector('.status-badge') as HTMLElement;

    expect(badge.textContent?.trim()).toBe('DEGRADED');
    expect(badge.classList.contains('degraded')).toBe(true);
  });

  it('should normalize status class names', () => {
    expect(component.getStatusClass('Needs Review')).toBe('needs-review');
  });

  it('should format a valid last seen timestamp', () => {
    const formatted = component.formatLastSeen('2026-09-06T06:30:00.000Z');

    expect(formatted).not.toBe('Unknown');
    expect(formatted).not.toBe('Never');
    expect(formatted.length).toBeGreaterThan(0);
  });

  it('should return Never for a missing timestamp', () => {
    expect(component.formatLastSeen(null)).toBe('Never');
  });

  it('should return Unknown for an invalid timestamp', () => {
    expect(component.formatLastSeen('not-a-date')).toBe('Unknown');
  });
});
