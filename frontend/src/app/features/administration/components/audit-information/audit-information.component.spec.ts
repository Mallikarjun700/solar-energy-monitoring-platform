import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AuditInformationComponent } from './audit-information.component';

describe('AuditInformationComponent', () => {
  let component: AuditInformationComponent;
  let fixture: ComponentFixture<AuditInformationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AuditInformationComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(AuditInformationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should expose operational traceability capabilities', () => {
    expect(component.traceabilityCapabilities).toHaveLength(5);
    expect(component.traceabilityCapabilities[0].name).toBe('Correlation ID');
  });

  it('should mark operational traceability as implemented', () => {
    expect(
      component.traceabilityCapabilities.every((capability) => capability.status === 'Implemented'),
    ).toBe(true);
  });

  it('should expose missing audit capabilities', () => {
    expect(component.auditCapabilities).toHaveLength(3);
    expect(component.auditCapabilities[0].name).toBe('Persistent audit history');
  });

  it('should identify audit capabilities as not implemented', () => {
    expect(
      component.auditCapabilities.every((capability) => capability.status === 'Not implemented'),
    ).toBe(true);
  });

  it('should render the audit heading', () => {
    const heading = fixture.nativeElement.querySelector('#audit-information-heading');

    expect(heading?.textContent?.trim()).toBe('Audit & Traceability');
  });

  it('should explain the absence of a user-facing audit API', () => {
    expect(fixture.nativeElement.textContent).toContain('Audit history is not currently exposed.');
  });

  it('should distinguish audit history from operational traceability', () => {
    expect(fixture.nativeElement.textContent).toContain('Operational logs and correlation IDs');
  });
});
