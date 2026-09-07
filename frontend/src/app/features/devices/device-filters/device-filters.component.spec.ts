import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DEFAULT_DEVICE_FILTERS, DeviceFilters } from '../models/device-filters.model';

import { DeviceFiltersComponent } from './device-filters.component';

describe('DeviceFiltersComponent', () => {
  let fixture: ComponentFixture<DeviceFiltersComponent>;
  let component: DeviceFiltersComponent;

  const filters: DeviceFilters = {
    ...DEFAULT_DEVICE_FILTERS,
  };

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [DeviceFiltersComponent],
    });

    fixture = TestBed.createComponent(DeviceFiltersComponent);

    fixture.componentRef.setInput('filters', filters);

    component = fixture.componentInstance;

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should emit search changes', () => {
    const emitSpy = vi.spyOn(component.searchChange, 'emit');

    component.onSearchChange('INV-001');

    expect(emitSpy).toHaveBeenCalledWith('INV-001');
  });

  it('should emit status changes', () => {
    const emitSpy = vi.spyOn(component.statusChange, 'emit');

    component.onStatusChange('ACTIVE');

    expect(emitSpy).toHaveBeenCalledWith('ACTIVE');
  });

  it('should emit a valid asset id', () => {
    const emitSpy = vi.spyOn(component.assetIdChange, 'emit');

    component.onAssetIdChange('25');

    expect(emitSpy).toHaveBeenCalledWith(25);
  });

  it('should emit null when asset id is cleared', () => {
    const emitSpy = vi.spyOn(component.assetIdChange, 'emit');

    component.onAssetIdChange('');

    expect(emitSpy).toHaveBeenCalledWith(null);
  });

  it('should ignore invalid asset ids', () => {
    const emitSpy = vi.spyOn(component.assetIdChange, 'emit');

    component.onAssetIdChange('abc');

    expect(emitSpy).not.toHaveBeenCalled();
  });

  it('should ignore zero asset id', () => {
    const emitSpy = vi.spyOn(component.assetIdChange, 'emit');

    component.onAssetIdChange('0');

    expect(emitSpy).not.toHaveBeenCalled();
  });

  it('should emit clear event', () => {
    const emitSpy = vi.spyOn(component.clear, 'emit');

    component.onClear();

    expect(emitSpy).toHaveBeenCalled();
  });

  it('should render the search input', () => {
    const element = fixture.nativeElement as HTMLElement;

    const input = element.querySelector('#device-search') as HTMLInputElement;

    expect(input).not.toBeNull();
    expect(input.type).toBe('search');
  });

  it('should render status options', () => {
    const element = fixture.nativeElement as HTMLElement;

    const select = element.querySelector('#device-status') as HTMLSelectElement;

    expect(select).not.toBeNull();

    const values = Array.from(select.options).map((option) => option.value);

    expect(values).toEqual(['', 'ACTIVE', 'INACTIVE']);
  });

  it('should render asset id input', () => {
    const element = fixture.nativeElement as HTMLElement;

    const input = element.querySelector('#device-asset-id') as HTMLInputElement;

    expect(input).not.toBeNull();
    expect(input.type).toBe('number');
  });

  it('should render a clear filters button', () => {
    const element = fixture.nativeElement as HTMLElement;

    const button = element.querySelector('.clear-button') as HTMLButtonElement;

    expect(button).not.toBeNull();
    expect(button.textContent).toContain('Clear Filters');
    expect(button.disabled).toBe(true);
  });

  it('should enable clear filters when a filter is active', () => {
    fixture.componentRef.setInput('filters', {
      search: 'INV',
      status: '',
      assetId: null,
    });

    fixture.detectChanges();

    const button = fixture.nativeElement.querySelector('.clear-button') as HTMLButtonElement;

    expect(button.disabled).toBe(false);
  });

  it('should provide labels for all filter controls', () => {
    const element = fixture.nativeElement as HTMLElement;

    const search = element.querySelector('#device-search');

    const status = element.querySelector('#device-status');

    const assetId = element.querySelector('#device-asset-id');

    expect(search).not.toBeNull();
    expect(status).not.toBeNull();
    expect(assetId).not.toBeNull();

    expect(element.querySelector('label[for="device-search"]')).not.toBeNull();

    expect(element.querySelector('label[for="device-status"]')).not.toBeNull();

    expect(element.querySelector('label[for="device-asset-id"]')).not.toBeNull();
  });

  it('should expose the filter container as a search region', () => {
    const element = fixture.nativeElement as HTMLElement;

    const container = element.querySelector('[role="search"]');

    expect(container).not.toBeNull();
    expect(container?.getAttribute('aria-label')).toBe('Filter devices');
  });

  it('should provide help text for search and asset id inputs', () => {
    const element = fixture.nativeElement as HTMLElement;

    const search = element.querySelector('#device-search');

    const assetId = element.querySelector('#device-asset-id');

    expect(search?.getAttribute('aria-describedby')).toBe('device-search-help');

    expect(assetId?.getAttribute('aria-describedby')).toBe('device-asset-help');
  });
});
