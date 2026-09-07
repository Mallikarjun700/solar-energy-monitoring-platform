import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PlantStatusFilters, DEFAULT_PLANT_FILTERS } from '../../models/plant-filters.model';
import { PlantFiltersComponent } from './plant-filters.component';

describe('PlantFiltersComponent', () => {
  let component: PlantFiltersComponent;
  let fixture: ComponentFixture<PlantFiltersComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PlantFiltersComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(PlantFiltersComponent);
    component = fixture.componentInstance;

    fixture.componentRef.setInput('filters', {
      ...DEFAULT_PLANT_FILTERS,
    });

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should emit search changes', () => {
    const searchSpy = vi.spyOn(component.searchChange, 'emit');

    component.onSearchChange('Bengaluru');

    expect(searchSpy).toHaveBeenCalledWith('Bengaluru');
  });

  it('should emit valid status changes', () => {
    const statusSpy = vi.spyOn(component.statusChange, 'emit');

    component.onStatusChange('ACTIVE');

    expect(statusSpy).toHaveBeenCalledWith('ACTIVE');
  });

  it('should ignore invalid status values', () => {
    const statusSpy = vi.spyOn(component.statusChange, 'emit');

    component.onStatusChange('INVALID');

    expect(statusSpy).not.toHaveBeenCalled();
  });

  it('should emit clear event', () => {
    const clearSpy = vi.spyOn(component.clear, 'emit');

    component.onClear();

    expect(clearSpy).toHaveBeenCalled();
  });

  it('should display the current search value', () => {
    const filters: PlantStatusFilters = {
      search: 'Alpha',
      status: 'ALL',
    };

    fixture.componentRef.setInput('filters', filters);
    fixture.detectChanges();

    const input = fixture.nativeElement.querySelector('#plant-search') as HTMLInputElement;

    expect(input.value).toBe('Alpha');
  });

  it('should display the current status value', () => {
    fixture.componentRef.setInput('filters', {
      search: '',
      status: 'MAINTENANCE',
    });

    fixture.detectChanges();

    const select = fixture.nativeElement.querySelector('#plant-status') as HTMLSelectElement;

    expect(select.value).toBe('MAINTENANCE');
  });

  it('should show clear button when filters are active', () => {
    fixture.componentRef.setInput('filters', {
      search: 'Alpha',
      status: 'ALL',
    });

    fixture.detectChanges();

    const button = fixture.nativeElement.querySelector('.clear-filters') as HTMLButtonElement;

    expect(button).toBeTruthy();
  });

  it('should hide clear button when default filters are active', () => {
    fixture.componentRef.setInput('filters', {
      ...DEFAULT_PLANT_FILTERS,
    });

    fixture.detectChanges();

    const button = fixture.nativeElement.querySelector('.clear-filters') as HTMLButtonElement;

    expect(button).toBeNull();
  });

  it('should associate the search input with its label', () => {
    const label = fixture.nativeElement.querySelector(
      'label[for="plant-search"]',
    ) as HTMLLabelElement;

    const input = fixture.nativeElement.querySelector('#plant-search') as HTMLInputElement;

    expect(label).toBeTruthy();
    expect(input).toBeTruthy();
    expect(label.htmlFor).toBe('plant-search');
  });

  it('should associate the status select with its label', () => {
    const label = fixture.nativeElement.querySelector(
      'label[for="plant-status"]',
    ) as HTMLLabelElement;

    const select = fixture.nativeElement.querySelector('#plant-status') as HTMLSelectElement;

    expect(label).toBeTruthy();
    expect(select).toBeTruthy();
    expect(label.htmlFor).toBe('plant-status');
  });

  it('should provide a search accessibility hint', () => {
    const input = fixture.nativeElement.querySelector('#plant-search') as HTMLInputElement;

    expect(input.getAttribute('aria-describedby')).toBe('plant-search-help');

    const help = fixture.nativeElement.querySelector('#plant-search-help') as HTMLElement;

    expect(help.textContent).toContain('Search by plant name, code, or location.');
  });
});
