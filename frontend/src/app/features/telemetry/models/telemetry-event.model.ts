export interface TelemetryEvent {
  id: number;
  eventId: string;
  tenantId: string;
  sourceId: string;
  eventType: string;
  eventTimestamp: string;
  receivedAt: string;
  schemaVersion: number;
  attributes: Record<string, unknown> | null;
  payload: Record<string, unknown> | null;
  createdAt: string;
}
