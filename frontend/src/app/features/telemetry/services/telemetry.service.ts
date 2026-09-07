import { HttpErrorResponse } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, map, throwError } from 'rxjs';

import { ApiService } from '../../../core/services/api.service';
import { ApiErrorService } from '../../../core/services/api-error.service';
import { TelemetryEvent } from '../models/telemetry-event.model';
import {
  TelemetryApiResource,
  TelemetryCursorApiResponse,
  TelemetryEventsApiResponse,
  TelemetryPaginationMeta,
} from '../models/telemetry-response.model';
import { TelemetryLatest, TelemetryLatestApiResponse } from '../models/telemetry-latest.model';

export interface TelemetryEventQuery {
  tenantId?: string;
  sourceId?: string;
  eventType?: string;
  from?: string;
  to?: string;
  perPage?: number;
  page?: number;
}

export interface TelemetryCursorQuery {
  perPage?: number;
  cursor?: string;
}

export interface TelemetryEventsResult {
  events: TelemetryEvent[];
  pagination: TelemetryPaginationMeta;
}

export interface TelemetryCursorResult {
  events: TelemetryEvent[];
  nextCursor: string | null;
  hasMore: boolean;
}

@Injectable({
  providedIn: 'root',
})
export class TelemetryService {
  private readonly api = inject(ApiService);
  private readonly apiErrorService = inject(ApiErrorService);

  getEvents(query: TelemetryEventQuery = {}): Observable<TelemetryEventsResult> {
    const params = this.buildEventQueryParams(query);

    return this.api.get<TelemetryEventsApiResponse>('/telemetry/events', params).pipe(
      map((response) => ({
        events: response.data.map((event) => this.mapEvent(event)),
        pagination: this.mapPagination(response),
      })),
      catchError((error: unknown) =>
        throwError(() => this.apiErrorService.normalize(error as HttpErrorResponse)),
      ),
    );
  }

  getEventsCursor(query: TelemetryCursorQuery = {}): Observable<TelemetryCursorResult> {
    const params: Record<string, string | number> = {};

    if (query.perPage !== undefined) {
      params['per_page'] = query.perPage;
    }

    if (query.cursor) {
      params['cursor'] = query.cursor;
    }

    return this.api.get<TelemetryCursorApiResponse>('/telemetry/events/cursor', params).pipe(
      map((response) => ({
        events: response.data.map((event) => this.mapEvent(event)),
        nextCursor: response.next_cursor,
        hasMore: response.next_cursor !== null,
      })),
      catchError((error: unknown) =>
        throwError(() => this.apiErrorService.normalize(error as HttpErrorResponse)),
      ),
    );
  }

  getLatest(deviceId: number, tenantId: string): Observable<TelemetryLatest> {
    const params: Record<string, string> = {
      tenant_id: tenantId,
    };

    return this.api
      .get<TelemetryLatestApiResponse>(`/telemetry/devices/${deviceId}/latest`, params)
      .pipe(
        map((response) => this.mapLatest(response)),
        catchError((error: unknown) =>
          throwError(() => this.apiErrorService.normalize(error as HttpErrorResponse)),
        ),
      );
  }

  private buildEventQueryParams(query: TelemetryEventQuery): Record<string, string | number> {
    const params: Record<string, string | number> = {};

    if (query.tenantId) {
      params['tenant_id'] = query.tenantId;
    }

    if (query.sourceId) {
      params['source_id'] = query.sourceId;
    }

    if (query.eventType) {
      params['event_type'] = query.eventType;
    }

    if (query.from) {
      params['from'] = query.from;
    }

    if (query.to) {
      params['to'] = query.to;
    }

    if (query.perPage !== undefined) {
      params['per_page'] = query.perPage;
    }

    if (query.page !== undefined) {
      params['page'] = query.page;
    }

    return params;
  }

  private mapEvent(resource: TelemetryApiResource): TelemetryEvent {
    return {
      id: resource.id,
      eventId: resource.event_id,
      tenantId: resource.tenant_id,
      sourceId: resource.source_id,
      eventType: resource.event_type,
      eventTimestamp: resource.event_timestamp,
      receivedAt: resource.received_at,
      schemaVersion: resource.schema_version,
      attributes: resource.attributes,
      payload: resource.payload,
      createdAt: resource.created_at,
    };
  }

  private mapPagination(response: TelemetryEventsApiResponse): TelemetryPaginationMeta {
    return {
      current_page: response.current_page,
      from: response.from,
      last_page: response.last_page,
      links: response.links,
      path: response.path,
      per_page: response.per_page,
      to: response.to,
      total: response.total,
    };
  }

  private mapLatest(response: TelemetryLatestApiResponse): TelemetryLatest {
    return {
      eventId: response.event_id,
      eventType: response.event_type,
      timestamp: response.timestamp,
      attributes: response.attributes,
      payload: response.payload,
    };
  }

  private extractNextCursor(nextPageUrl: string | null): string | null {
    if (!nextPageUrl) {
      return null;
    }

    try {
      const url = new URL(nextPageUrl, window.location.origin);
      return url.searchParams.get('cursor');
    } catch {
      const queryString = nextPageUrl.split('?')[1];

      if (!queryString) {
        return null;
      }

      return new URLSearchParams(queryString).get('cursor');
    }
  }
}
