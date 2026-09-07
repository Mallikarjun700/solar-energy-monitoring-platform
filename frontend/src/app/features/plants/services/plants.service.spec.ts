import { TestBed } from '@angular/core/testing';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';

import { PlantsService } from './plants.service';
import { ApiErrorService } from '../../../core/services/api-error.service';
import { environment } from '../../../../environments/environment';

describe('PlantsService', () => {
  let service: PlantsService;
  let httpTesting: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [PlantsService, ApiErrorService, provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(PlantsService);
    httpTesting = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpTesting.verify();
  });

  it('should load and map plants', () => {
    service.getPlants().subscribe((plants) => {
      expect(plants).toEqual([
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
      ]);
    });

    const request = httpTesting.expectOne(`${environment.apiBaseUrl}/plants`);

    expect(request.request.method).toBe('GET');

    request.flush({
      status: 'success',
      message: 'Plants retrieved successfully.',
      data: [
        {
          id: 1,
          name: 'Solar Plant Alpha',
          code: 'SPA-001',
          location: 'Bengaluru',
          capacity_kw: '500.00',
          status: 'ACTIVE',
          created_at: '2026-08-01T10:00:00Z',
          updated_at: '2026-08-02T10:00:00Z',
        },
      ],
    });
  });

  it('should preserve null capacity', () => {
    service.getPlants().subscribe((plants) => {
      expect(plants[0].capacityKw).toBeNull();
    });

    const request = httpTesting.expectOne(`${environment.apiBaseUrl}/plants`);

    request.flush({
      status: 'success',
      message: 'Plants retrieved successfully.',
      data: [
        {
          id: 2,
          name: 'Solar Plant Beta',
          code: 'SPB-001',
          location: null,
          capacity_kw: null,
          status: 'INACTIVE',
          created_at: '2026-08-01T10:00:00Z',
          updated_at: '2026-08-02T10:00:00Z',
        },
      ],
    });
  });

  it('should load and map a single plant', () => {
    service.getPlant(7).subscribe((plant) => {
      expect(plant).toEqual({
        id: 7,
        name: 'Solar Plant Gamma',
        code: 'SPG-001',
        location: 'Mysuru',
        capacityKw: 750,
        status: 'MAINTENANCE',
        createdAt: '2026-08-03T10:00:00Z',
        updatedAt: '2026-08-04T10:00:00Z',
      });
    });

    const request = httpTesting.expectOne(`${environment.apiBaseUrl}/plants/7`);

    expect(request.request.method).toBe('GET');

    request.flush({
      status: 'success',
      message: 'Plant retrieved successfully.',
      data: {
        id: 7,
        name: 'Solar Plant Gamma',
        code: 'SPG-001',
        location: 'Mysuru',
        capacity_kw: '750.00',
        status: 'MAINTENANCE',
        created_at: '2026-08-03T10:00:00Z',
        updated_at: '2026-08-04T10:00:00Z',
      },
    });
  });

  it('should normalize API errors', () => {
    service.getPlants().subscribe({
      next: () => {
        throw new Error('Expected request to fail');
      },
      error: (error) => {
        expect(error.status).toBe(500);
        expect(error.message).toBeTruthy();
      },
    });

    const request = httpTesting.expectOne(`${environment.apiBaseUrl}/plants`);

    request.flush(
      {
        message: 'Unable to retrieve plants.',
      },
      {
        status: 500,
        statusText: 'Server Error',
      },
    );
  });
});
