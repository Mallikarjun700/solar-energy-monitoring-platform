export interface ApiError {
  message: string;
  error?: string;
  status: number;
  correlationId?: string;
  errors?: Record<string, string[]>;
}
