import { Injectable } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';

import { ApiError } from '../models/api-error.model';

@Injectable({
  providedIn: 'root',
})
export class ApiErrorService {
  normalize(error: HttpErrorResponse): ApiError {
    const body = this.toRecord(error.error);

    return {
      message: this.resolveMessage(body, error),
      error: this.resolveError(body),
      status: error.status,
      correlationId: this.resolveCorrelationId(error, body),
      errors: this.resolveValidationErrors(body),
    };
  }

  private toRecord(value: unknown): Record<string, unknown> {
    if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
      return value as Record<string, unknown>;
    }

    return {};
  }

  private resolveMessage(body: Record<string, unknown>, error: HttpErrorResponse): string {
    if (typeof body['message'] === 'string') {
      return body['message'];
    }

    if (error.status === 0) {
      return 'Unable to reach the server.';
    }

    return 'An unexpected error occurred.';
  }

  private resolveError(body: Record<string, unknown>): string | undefined {
    return typeof body['error'] === 'string' ? body['error'] : undefined;
  }

  private resolveCorrelationId(
    error: HttpErrorResponse,
    body: Record<string, unknown>,
  ): string | undefined {
    const headerValue = error.headers.get('X-Correlation-ID');

    if (headerValue) {
      return headerValue;
    }

    return typeof body['correlation_id'] === 'string' ? body['correlation_id'] : undefined;
  }

  private resolveValidationErrors(
    body: Record<string, unknown>,
  ): Record<string, string[]> | undefined {
    const errors = body['errors'];

    if (typeof errors !== 'object' || errors === null || Array.isArray(errors)) {
      return undefined;
    }

    return errors as Record<string, string[]>;
  }
}
