import { TestBed } from '@angular/core/testing';
import {
  provideHttpClient,
} from '@angular/common/http';
import {
  HttpTestingController,
  provideHttpClientTesting,
} from '@angular/common/http/testing';

import { AssetsService } from './assets.service';
import { environment } from '../../../../environments/environment';

describe('AssetsService', () => {
  let service: AssetsService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        AssetsService,
        provideHttpClient(),
        provideHttpClientTesting(),
      ],
    });

    service = TestBed.inject(AssetsService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => httpMock.verify());

  it('gets assets and maps snake_case fields', () => {
    service.getAssets({ plantId: 10 }).subscribe((assets) => {
      expect(assets[0]).toEqual(
        expect.objectContaining({
          id: 1,
          plantId: 10,
          assetType: 'INVERTER',
          serialNumber: 'INV-001',
        }),
      );
    });

    const request = httpMock.expectOne(
      (req) =>
        req.url === `${environment.apiBaseUrl}/assets` &&
        req.params.get('plant_id') === '10',
    );

    request.flush({
      data: [
        {
          id: 1,
          plant_id: 10,
          name: 'Inverter 1',
          asset_type: 'INVERTER',
          serial_number: 'INV-001',
          status: 'ACTIVE',
          location: 'Block A',
          created_at: '2026-01-01T00:00:00Z',
          updated_at: '2026-01-01T00:00:00Z',
        },
      ],
    });
  });

  it('creates an asset', () => {
    const payload = {
      plant_id: 10,
      name: 'Inverter 1',
      asset_type: 'INVERTER',
      serial_number: 'INV-001',
      status: 'ACTIVE' as const,
    };

    service.createAsset(payload).subscribe((asset) => {
      expect(asset.id).toBe(1);
      expect(asset.plantId).toBe(10);
    });

    const request = httpMock.expectOne(
      `${environment.apiBaseUrl}/assets`,
    );

    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual(payload);

    request.flush({
      message: 'Asset created successfully.',
      data: {
        id: 1,
        plant_id: 10,
        name: 'Inverter 1',
        asset_type: 'INVERTER',
        serial_number: 'INV-001',
        status: 'ACTIVE',
        location: null,
        created_at: '2026-01-01T00:00:00Z',
        updated_at: '2026-01-01T00:00:00Z',
      },
    });
  });

  it('updates an asset', () => {
    service.updateAsset(1, { status: 'MAINTENANCE' }).subscribe();

    const request = httpMock.expectOne(
      `${environment.apiBaseUrl}/assets/1`,
    );

    expect(request.request.method).toBe('PUT');
    expect(request.request.body).toEqual({
      status: 'MAINTENANCE',
    });

    request.flush({
      message: 'Asset updated successfully.',
      data: {
        id: 1,
        plant_id: 10,
        name: 'Inverter 1',
        asset_type: 'INVERTER',
        serial_number: 'INV-001',
        status: 'MAINTENANCE',
        location: null,
        created_at: '2026-01-01T00:00:00Z',
        updated_at: '2026-01-01T00:00:00Z',
      },
    });
  });

  it('deletes an asset', () => {
    service.deleteAsset(1).subscribe();

    const request = httpMock.expectOne(
      `${environment.apiBaseUrl}/assets/1`,
    );

    expect(request.request.method).toBe('DELETE');
    request.flush(null);
  });
});