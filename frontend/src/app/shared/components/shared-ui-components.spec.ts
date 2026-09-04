import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Component } from '@angular/core';
import { describe, expect, it } from 'vitest';
import { PageHeader } from './page-header/page-header';
import { LoadingState } from './loading-state/loading-state';
import { EmptyState } from './empty-state/empty-state';
import { ErrorState } from './error-state/error-state';

@Component({
  standalone: true,
  imports: [PageHeader, LoadingState, EmptyState, ErrorState],
  template: `
    <app-page-header title="Telemetry" description="Monitor plant telemetry." />

    <app-loading-state message="Loading telemetry..." />

    <app-empty-state title="No telemetry" message="No telemetry is available." />

    <app-error-state
      title="Telemetry unavailable"
      message="Please try again."
      (retry)="retryCalled = true"
    />
  `,
})
class SharedUiHost {
  retryCalled = false;
}

describe('Shared UI Components', () => {
  let fixture: ComponentFixture<SharedUiHost>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SharedUiHost],
    }).compileComponents();

    fixture = TestBed.createComponent(SharedUiHost);
    fixture.detectChanges();
  });

  it('should render the page header', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelector('.page-header__title')?.textContent).toContain('Telemetry');

    expect(element.querySelector('.page-header__description')?.textContent).toContain(
      'Monitor plant telemetry.',
    );
  });

  it('should render the loading state', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelector('.loading-state__message')?.textContent).toContain(
      'Loading telemetry...',
    );
  });

  it('should render the empty state', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelector('.empty-state__title')?.textContent).toContain('No telemetry');
  });

  it('should emit retry from the error state', () => {
    const button = fixture.nativeElement.querySelector('.error-state__retry') as HTMLButtonElement;

    button.click();
    fixture.detectChanges();

    expect(fixture.componentInstance.retryCalled).toBe(true);
  });
});
