import { HttpHandlerFn, HttpRequest } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';

import { authInterceptor } from './auth.interceptor';
import { TokenStorageService } from '../auth/token-storage.service';

describe('authInterceptor', () => {
  let tokenStorage: {
    getToken: ReturnType<typeof vi.fn>;
  };

  beforeEach(() => {
    tokenStorage = {
      getToken: vi.fn(),
    };

    TestBed.configureTestingModule({
      providers: [
        {
          provide: TokenStorageService,
          useValue: tokenStorage,
        },
      ],
    });
  });

  it('adds the bearer token when a token exists', () => {
    tokenStorage.getToken.mockReturnValue('test-token');

    const request = new HttpRequest('GET', '/api/v1/auth/me');

    const next: HttpHandlerFn = (req) => {
      expect(req.headers.get('Authorization')).toBe('Bearer test-token');

      return of();
    };

    TestBed.runInInjectionContext(() => {
      authInterceptor(request, next).subscribe();
    });
  });

  it('does not add authorization when no token exists', () => {
    tokenStorage.getToken.mockReturnValue(null);

    const request = new HttpRequest('GET', '/api/v1/health');

    const next: HttpHandlerFn = (req) => {
      expect(req.headers.has('Authorization')).toBe(false);

      return of();
    };

    TestBed.runInInjectionContext(() => {
      authInterceptor(request, next).subscribe();
    });
  });
});
