import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { Plant } from '../../models/plant.model';
import { PlantListComponent } from './plant-list.component';

describe('PlantListComponent', () => {
  let component: PlantListComponent;
  let fixture: ComponentFixture<PlantListComponent>;

  const plants: Plant[] = [
    {
      id: 1,
      name: 'Solar Plant Alpha',
      code: 'SPA-001',
      location: 'Bengaluru',
      capacityKw: 500,
      status: 'ACTIVE',
      createdAt: '2026-08-01T10:00:00Z',
      updatedAt: '2026-08-02T10:00:00Z',
    },
    {
      id: 2,
      name: 'Solar Plant Beta',
      code: 'SPB-001',
      location: '',
      capacityKw: null,
      status: 'INACTIVE',
      createdAt: '2026-08-01T10:00:00Z',
      updatedAt: '2026-08-02T10:00:00Z',
    },
  ];

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PlantListComponent],
      providers: [provideRouter([])],
    }).compileComponents();

    fixture = TestBed.createComponent(PlantListComponent);
    component = fixture.componentInstance;

    fixture.componentRef.setInput('plants', plants);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render all plants', () => {
    const cards = fixture.nativeElement.querySelectorAll('.plant-card');

    expect(cards.length).toBe(2);
  });

  it('should render plant name and code', () => {
    const card = fixture.nativeElement.querySelector('.plant-card') as HTMLElement;

    expect(card.textContent).toContain('Solar Plant Alpha');
    expect(card.textContent).toContain('SPA-001');
  });

  it('should render plant status', () => {
    const statuses = fixture.nativeElement.querySelectorAll('.plant-status');

    expect(statuses[0].textContent.trim()).toBe('ACTIVE');
    expect(statuses[1].textContent.trim()).toBe('INACTIVE');
  });

  it('should render capacity when available', () => {
    const card = fixture.nativeElement.querySelector('.plant-card') as HTMLElement;

    expect(card.textContent).toContain('500 kW');
  });

  it('should render fallback when capacity is unavailable', () => {
    const cards = fixture.nativeElement.querySelectorAll('.plant-card');

    expect(cards[1].textContent).toContain('Not specified');
  });

  it('should render fallback when location is unavailable', () => {
    const cards = fixture.nativeElement.querySelectorAll('.plant-card');

    expect(cards[1].textContent).toContain('Not specified');
  });

  it('should render an empty message when no plants are provided', () => {
    fixture.componentRef.setInput('plants', []);
    fixture.detectChanges();

    const emptyState = fixture.nativeElement.querySelector('.empty-list') as HTMLElement;

    expect(emptyState).toBeTruthy();
    expect(emptyState.textContent).toContain('No plants match the current filters.');
  });
});
