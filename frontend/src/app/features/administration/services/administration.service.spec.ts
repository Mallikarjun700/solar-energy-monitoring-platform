import { TestBed } from '@angular/core/testing';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpErrorResponse } from '@angular/common/http';

import { AdministrationService } from './administration.service';

describe('AdministrationService', () => {
  let service: AdministrationService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [AdministrationService, provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(AdministrationService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should load the authenticated user profile', () => {
    const response = {
      status: 'success',
      message: 'Authenticated user.',
      data: {
        user: {
          id: 1,
          name: 'Admin User',
          email: 'admin@example.com',
          role: 'admin',
        },
        abilities: [
          'telemetry:read',
          'telemetry:write',
          'alerts:read',
          'alerts:acknowledge',
          'alerts:resolve',
          'dlq:read',
          'dlq:replay',
        ],
      },
    };

    service.getProfile().subscribe((profile) => {
      expect(profile.user.id).toBe(1);
      expect(profile.user.name).toBe('Admin User');
      expect(profile.user.email).toBe('admin@example.com');
      expect(profile.user.role).toBe('admin');

      expect(profile.abilities).toHaveLength(7);

      expect(profile.abilities[0]).toEqual({
        name: 'telemetry:read',
        label: 'Telemetry Read',
      });

      expect(profile.abilities[3]).toEqual({
        name: 'alerts:acknowledge',
        label: 'Alerts Acknowledge',
      });
    });

    const request = httpMock.expectOne('http://localhost:8000/api/v1/auth/me');

    expect(request.request.method).toBe('GET');

    request.flush(response);
  });

  it('should preserve backend ability names', () => {
    const response = {
      status: 'success',
      message: 'Authenticated user.',
      data: {
        user: {
          id: 2,
          name: 'Operator',
          email: 'operator@example.com',
          role: 'operator',
        },
        abilities: ['telemetry:read', 'telemetry:write'],
      },
    };

    service.getProfile().subscribe((profile) => {
      expect(profile.abilities.map((ability) => ability.name)).toEqual([
        'telemetry:read',
        'telemetry:write',
      ]);
    });

    const request = httpMock.expectOne('http://localhost:8000/api/v1/auth/me');
    request.flush(response);
  });

  it('should handle an ability without a resource separator', () => {
    const response = {
      status: 'success',
      message: 'Authenticated user.',
      data: {
        user: {
          id: 3,
          name: 'Viewer',
          email: 'viewer@example.com',
          role: 'viewer',
        },
        abilities: ['customability'],
      },
    };

    service.getProfile().subscribe((profile) => {
      expect(profile.abilities).toEqual([
        {
          name: 'customability',
          label: 'customability',
        },
      ]);
    });

    const request = httpMock.expectOne('http://localhost:8000/api/v1/auth/me');
    request.flush(response);
  });

  it('should propagate API errors', () => {
    let receivedError: HttpErrorResponse | undefined;

    service.getProfile().subscribe({
      //next: () => fail('Expected the request to fail'),
      error: (error: HttpErrorResponse) => {
        receivedError = error;
      },
    });

    const request = httpMock.expectOne('http://localhost:8000/api/v1/auth/me');

    request.flush(
      {
        status: 'error',
        message: 'Unauthenticated.',
      },
      {
        status: 401,
        statusText: 'Unauthorized',
      },
    );

    expect(receivedError?.status).toBe(401);
  });
});
