export interface TelemetryLatestApiResponse {
  event_id: string;
  event_type: string;
  timestamp: string;
  attributes: Record<string, unknown> | null;
  payload: Record<string, unknown> | null;
}

export interface TelemetryLatest {
  eventId: string;
  eventType: string;
  timestamp: string;
  attributes: Record<string, unknown> | null;
  payload: Record<string, unknown> | null;
}
