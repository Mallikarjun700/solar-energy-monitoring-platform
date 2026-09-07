import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { Plant } from '../../models/plant.model';
import { PlantCardComponent } from './plant-card.component';

describe('PlantCardComponent', () => {
  let component: PlantCardComponent;
  let fixture: ComponentFixture<PlantCardComponent>;

  const plant: Plant = {
    id: 1,
    name: 'Solar Plant Alpha',
    code: 'SPA-001',
    location: 'Bengaluru',
    capacityKw: 500,
    status: 'ACTIVE',
    createdAt: '2026-08-01T10:00:00Z',
    updatedAt: '2026-08-02T10:00:00Z',
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PlantCardComponent],
      providers: [provideRouter([])],
    }).compileComponents();

    fixture = TestBed.createComponent(PlantCardComponent);
    component = fixture.componentInstance;

    fixture.componentRef.setInput('plant', plant);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render the plant name', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Solar Plant Alpha');
  });

  it('should render the plant code', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('SPA-001');
  });

  it('should render the plant status', () => {
    const status = fixture.nativeElement.querySelector('.plant-status') as HTMLElement;

    expect(status.textContent?.trim()).toBe('ACTIVE');
    expect(status.getAttribute('data-status')).toBe('ACTIVE');
  });

  it('should render the plant location', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Bengaluru');
  });

  it('should render capacity when available', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('500 kW');
  });

  it('should render a fallback for missing location', () => {
    fixture.componentRef.setInput('plant', {
      ...plant,
      location: null,
    });

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Not specified');
  });

  it('should render a fallback for missing capacity', () => {
    fixture.componentRef.setInput('plant', {
      ...plant,
      capacityKw: null,
    });

    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Not specified');
  });

  it('should render maintenance status correctly', () => {
    fixture.componentRef.setInput('plant', {
      ...plant,
      status: 'MAINTENANCE',
    });

    fixture.detectChanges();

    const status = fixture.nativeElement.querySelector('.plant-status') as HTMLElement;

    expect(status.textContent?.trim()).toBe('MAINTENANCE');
    expect(status.getAttribute('data-status')).toBe('MAINTENANCE');
  });

  it('should render the plant name as a heading', () => {
    const heading = fixture.nativeElement.querySelector('h2') as HTMLElement;

    expect(heading).toBeTruthy();
    expect(heading.textContent).toContain('Solar Plant Alpha');
  });

  it('should expose the plant status accessibly', () => {
    const status = fixture.nativeElement.querySelector('.plant-status') as HTMLElement;

    expect(status.getAttribute('aria-label')).toBe('Plant status: ACTIVE');
  });

  it('should link to the plant details route', () => {
    const link = fixture.nativeElement.querySelector(
      '.plant-card__identity h2 a',
    ) as HTMLAnchorElement;

    expect(link).toBeTruthy();
    expect(link.getAttribute('href')).toBe('/plants/1');
  });
});
