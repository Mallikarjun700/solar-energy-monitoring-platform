import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { describe, expect, it, vi } from 'vitest';

import { ApiErrorService } from '../../core/services/api-error.service';
import { PlantsComponent } from './plants.component';
import { PlantsService } from './services/plants.service';

describe('PlantsComponent', () => {
  let fixture: ComponentFixture<PlantsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PlantsComponent],
      providers: [
        {
          provide: PlantsService,
          useValue: {
            getPlants: vi.fn(() => of([])),
          },
        },
        {
          provide: ApiErrorService,
          useValue: {
            normalize: vi.fn(),
          },
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(PlantsComponent);
    fixture.detectChanges();
  });

  it('should create the component', () => {
    expect(fixture.componentInstance).toBeTruthy();
  });

  it('should render the empty state when no plants are returned', () => {
    expect(fixture.nativeElement.textContent).toContain('No plants found');
  });
});
