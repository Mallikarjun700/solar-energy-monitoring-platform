import { HttpHandlerFn, HttpRequest, HttpResponse } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { describe, expect, it, vi } from 'vitest';

import { correlationIdInterceptor } from './correlation-id.interceptor';

describe('correlationIdInterceptor', () => {
  it('adds a correlation id to outgoing requests', () => {
    TestBed.runInInjectionContext(() => {
      const request = new HttpRequest('GET', '/api/v1/test');

      const next: HttpHandlerFn = vi.fn((req) => {
        expect(req.headers.has('X-Correlation-ID')).toBe(true);
        expect(req.headers.get('X-Correlation-ID')).toMatch(
          /^[0-9a-f]{8}-[0-9a-f]{4}-[4-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i,
        );

        return of(
          new HttpResponse({
            status: 200,
          }),
        );
      });

      correlationIdInterceptor(request, next);

      expect(next).toHaveBeenCalledTimes(1);
    });
  });
});
