import { HttpInterceptorFn } from '@angular/common/http';

export const correlationIdInterceptor: HttpInterceptorFn = (req, next) => {
  const correlationId = crypto.randomUUID();

  const request = req.clone({
    setHeaders: {
      'X-Correlation-ID': correlationId
    }
  });

  return next(request);
};
