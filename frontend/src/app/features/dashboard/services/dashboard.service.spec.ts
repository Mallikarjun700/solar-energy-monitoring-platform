import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';

import { ApiService } from '../../../core/services/api.service';
import { TenantContextService } from '../../../core/tenant/tenant-context.service';
import { DashboardService } from './dashboard.service';

describe('DashboardService', () => {
  let service: DashboardService;

  const apiServiceMock = {
    get: vi.fn(),
  };

  const tenantContextMock = {
    getTenantId: vi.fn(() => '11111111-1111-4111-8111-111111111111'),
  };

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        DashboardService,
        {
          provide: ApiService,
          useValue: apiServiceMock,
        },
        {
          provide: TenantContextService,
          useValue: tenantContextMock,
        },
      ],
    });

    apiServiceMock.get.mockReset();
    tenantContextMock.getTenantId.mockClear();

    service = TestBed.inject(DashboardService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should request dashboard data for the current tenant', () => {
    const dashboard = {
      kpis: {
        totalPlants: 3,
        totalDevices: 12,
        activeDevices: 10,
        currentPowerKw: 427.5,
        todayEnergyKwh: null,
      },
      plants: [],
      devices: [],
      alerts: [],
      recentTelemetry: [],
    };

    apiServiceMock.get.mockReturnValue(
      of({
        status: 'success',
        message: 'Dashboard data retrieved successfully.',
        data: dashboard,
      }),
    );

    service.getDashboard().subscribe((result) => {
      expect(result).toEqual(dashboard);
    });

    expect(tenantContextMock.getTenantId).toHaveBeenCalledOnce();

    expect(apiServiceMock.get).toHaveBeenCalledWith('/dashboard', {
      tenant_id: '11111111-1111-4111-8111-111111111111',
    });
  });

  it('should unwrap the API response data', () => {
    const dashboard = {
      kpis: {
        totalPlants: 0,
        totalDevices: 0,
        activeDevices: 0,
        currentPowerKw: null,
        todayEnergyKwh: null,
      },
      plants: [],
      devices: [],
      alerts: [],
      recentTelemetry: [],
    };

    apiServiceMock.get.mockReturnValue(
      of({
        status: 'success',
        message: 'Dashboard data retrieved successfully.',
        data: dashboard,
        correlation_id: 'test-correlation-id',
      }),
    );

    service.getDashboard().subscribe((result) => {
      expect(result).toEqual(dashboard);
      expect(result).not.toHaveProperty('status');
      expect(result).not.toHaveProperty('message');
      expect(result).not.toHaveProperty('correlation_id');
    });
  });
});
