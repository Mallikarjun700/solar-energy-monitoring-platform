import { Component } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, provideRouter } from '@angular/router';
import { Location } from '@angular/common';
import { of, throwError } from 'rxjs';

import { PlantDetailsComponent } from './plant-details.component';
import { PlantsService } from '../services/plants.service';
import { Plant } from '../models/plant.model';

@Component({
  standalone: true,
  template: '',
})
class EmptyComponent {}

describe('PlantDetailsComponent', () => {
  let fixture: ComponentFixture<PlantDetailsComponent>;
  let component: PlantDetailsComponent;
  let plantsService: {
    getPlant: ReturnType<typeof vi.fn>;
  };

  const plant: Plant = {
    id: 1,
    name: 'Solar Plant Alpha',
    code: 'SPA-001',
    location: 'Bengaluru',
    capacityKw: 500,
    status: 'ACTIVE',
    createdAt: '2026-08-20T10:00:00.000Z',
    updatedAt: '2026-08-25T12:00:00.000Z',
  };

  function createComponent(routeId = '1'): void {
    TestBed.configureTestingModule({
      imports: [PlantDetailsComponent],
      providers: [
        provideRouter([
          {
            path: 'plants',
            component: EmptyComponent,
          },
        ]),
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: {
                get: vi.fn().mockReturnValue(routeId),
              },
            },
          },
        },
        {
          provide: PlantsService,
          useValue: plantsService,
        },
      ],
    });

    fixture = TestBed.createComponent(PlantDetailsComponent);
    component = fixture.componentInstance;
  }

  beforeEach(() => {
    plantsService = {
      getPlant: vi.fn().mockReturnValue(of(plant)),
    };
  });

  it('should create', () => {
    createComponent();

    expect(component).toBeTruthy();
  });

  it('should load the plant using the route id', () => {
    createComponent('42');

    expect(plantsService.getPlant).toHaveBeenCalledWith(42);
  });

  it('should display plant information', () => {
    createComponent();

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Solar Plant Alpha');
    expect(element.textContent).toContain('SPA-001');
    expect(element.textContent).toContain('Bengaluru');
    expect(element.textContent).toContain('500 kW');
    expect(element.textContent).toContain('ACTIVE');
  });

  it('should display created and updated timestamps', () => {
    createComponent();

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Created');
    expect(element.textContent).toContain('Last Updated');
  });

  it('should show an error when loading fails', () => {
    plantsService.getPlant.mockReturnValue(
      throwError(() => ({
        message: 'Plant not found.',
      })),
    );

    createComponent();

    fixture.detectChanges();

    expect(component.error()).toBe('Plant not found.');

    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Plant not found.');
    expect(element.textContent).toContain('Back to Plants');
  });

  it('should show an invalid-id error without calling the API', () => {
    createComponent('invalid');

    fixture.detectChanges();

    expect(plantsService.getPlant).not.toHaveBeenCalled();
    expect(component.error()).toBe('The requested plant could not be identified.');
  });

  it('should handle a null location and capacity', () => {
    plantsService.getPlant.mockReturnValue(
      of({
        ...plant,
        location: null,
        capacityKw: null,
      }),
    );

    createComponent();

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Not available');
  });

  it('should format capacity correctly', () => {
    createComponent();

    expect(component.formatCapacity(500)).toBe('500 kW');
    expect(component.formatCapacity(1250.5)).toBe('1,250.5 kW');
    expect(component.formatCapacity(null)).toBe('Not available');
  });

  it('should format valid dates', () => {
    createComponent();

    const formatted = component.formatDate('2026-08-20T10:00:00.000Z');

    expect(formatted).not.toBe('Not available');
  });

  it('should handle invalid dates', () => {
    createComponent();

    expect(component.formatDate('invalid-date')).toBe('Not available');
  });

  it('should expose the loaded plant through the signal', () => {
    createComponent();

    fixture.detectChanges();

    expect(component.plant()).toEqual(plant);
    expect(component.loading()).toBe(false);
    expect(component.error()).toBeNull();
  });

  it('should allow retry after an error', () => {
    plantsService.getPlant
      .mockReturnValueOnce(
        throwError(() => ({
          message: 'Temporary failure.',
        })),
      )
      .mockReturnValueOnce(of(plant));

    createComponent();

    fixture.detectChanges();

    expect(component.error()).toBe('Temporary failure.');

    component.loadPlant();

    fixture.detectChanges();

    expect(plantsService.getPlant).toHaveBeenCalledTimes(2);
    expect(component.plant()).toEqual(plant);
    expect(component.error()).toBeNull();
  });

  it('should provide a back navigation action', () => {
    createComponent();

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;
    const backButton = element.querySelector('button.back-button');

    expect(backButton).not.toBeNull();
    expect(backButton?.textContent).toContain('Back to Plants');
  });

  it('should expose the Plants route as a fallback navigation link', () => {
    plantsService.getPlant.mockReturnValue(
      throwError(() => ({
        message: 'Plant not found.',
      })),
    );

    createComponent();

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;
    const links = Array.from(element.querySelectorAll('a'));

    expect(links.some((link) => link.getAttribute('href') === '/plants')).toBe(true);
  });
});
