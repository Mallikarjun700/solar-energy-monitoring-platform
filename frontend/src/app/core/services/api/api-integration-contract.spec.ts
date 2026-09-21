import { TestBed } from '@angular/core/testing';
import {
  provideHttpClient,
  withInterceptors,
} from '@angular/common/http';
import {
  HttpTestingController,
  provideHttpClientTesting,
} from '@angular/common/http/testing';

import { ApiService } from '../api.service';
import { environment } from '../../../../environments/environment';

describe('ApiService integration contract', () => {
  let api: ApiService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        ApiService,
        provideHttpClient(),
        provideHttpClientTesting(),
      ],
    });

    api = TestBed.inject(ApiService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('builds API URLs consistently', () => {
    api.get<{ data: string[] }>('/plants').subscribe();

    const request = httpMock.expectOne(
      `${environment.apiBaseUrl}/plants`,
    );

    expect(request.request.method).toBe('GET');
    request.flush({ data: [] });
  });

  it('serializes query parameters', () => {
    api
      .get('/devices', {
        asset_id: 10,
        status: 'ONLINE',
      })
      .subscribe();

    const request = httpMock.expectOne(
      (req) =>
        req.url === `${environment.apiBaseUrl}/devices` &&
        req.params.get('asset_id') === '10' &&
        req.params.get('status') === 'ONLINE',
    );

    expect(request.request.method).toBe('GET');
    request.flush({ data: [] });
  });

  it('sends POST request bodies unchanged', () => {
    const payload = {
      name: 'Plant A',
      code: 'PLANT-A',
      capacity_kw: 500,
    };

    api.post('/plants', payload).subscribe();

    const request = httpMock.expectOne(
      `${environment.apiBaseUrl}/plants`,
    );

    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual(payload);

    request.flush({
      message: 'Plant created successfully.',
      data: {
        id: 1,
        name: 'Plant A',
        code: 'PLANT-A',
        location: null,
        capacity_kw: 500,
        status: 'ACTIVE',
        created_at: '2026-01-01T00:00:00Z',
        updated_at: '2026-01-01T00:00:00Z',
      },
    });
  });
});