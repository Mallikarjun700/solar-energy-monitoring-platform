import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  selector: 'app-audit-information',
  standalone: true,
  templateUrl: './audit-information.component.html',
  styleUrl: './audit-information.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AuditInformationComponent {
  readonly traceabilityCapabilities = [
    {
      name: 'Correlation ID',
      status: 'Implemented',
      description: 'Requests can be associated with a correlation identifier.',
    },
    {
      name: 'Request tracing',
      status: 'Implemented',
      description: 'Correlation IDs are propagated through backend request processing.',
    },
    {
      name: 'Idempotency correlation',
      status: 'Implemented',
      description: 'Idempotency records retain correlation information for request tracing.',
    },
    {
      name: 'Queue/job correlation',
      status: 'Implemented',
      description:
        'Telemetry jobs preserve correlation information during asynchronous processing.',
    },
    {
      name: 'DLQ correlation',
      status: 'Implemented',
      description: 'Dead-letter processing can retain correlation information for investigation.',
    },
  ];

  readonly auditCapabilities = [
    {
      name: 'Persistent audit history',
      status: 'Not implemented',
    },
    {
      name: 'Audit-log API',
      status: 'Not implemented',
    },
    {
      name: 'User activity history',
      status: 'Not implemented',
    },
  ];
}
