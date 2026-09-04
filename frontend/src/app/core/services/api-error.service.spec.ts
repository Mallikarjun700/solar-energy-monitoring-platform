import { HttpErrorResponse, HttpHeaders } from '@angular/common/http';
import { describe, expect, it } from 'vitest';

import { ApiErrorService } from './api-error.service';

describe('ApiErrorService', () => {
  const service = new ApiErrorService();

  it('normalizes a standard API error', () => {
    const error = new HttpErrorResponse({
      status: 422,
      error: {
        message: 'Validation failed.',
        error: 'validation_error',
        errors: {
          email: ['The email field is required.'],
        },
      },
      headers: new HttpHeaders({
        'X-Correlation-ID': 'correlation-123',
      }),
    });

    const result = service.normalize(error);

    expect(result.status).toBe(422);
    expect(result.message).toBe('Validation failed.');
    expect(result.error).toBe('validation_error');
    expect(result.correlationId).toBe('correlation-123');
    expect(result.errors?.['email']).toEqual(['The email field is required.']);
  });

  it('handles network errors', () => {
    const error = new HttpErrorResponse({
      status: 0,
      error: new ProgressEvent('error'),
    });

    const result = service.normalize(error);

    expect(result.status).toBe(0);
    expect(result.message).toBe('Unable to reach the server.');
  });

  it('uses a generic message for unknown server errors', () => {
    const error = new HttpErrorResponse({
      status: 500,
      error: {
        unexpected: true,
      },
    });

    const result = service.normalize(error);

    expect(result.status).toBe(500);
    expect(result.message).toBe('An unexpected error occurred.');
  });
});
