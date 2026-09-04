import { Component, input, output } from '@angular/core';

@Component({
  imports: [],
  selector: 'app-error-state',
  styleUrl: './error-state.scss',
  templateUrl: './error-state.html',
})
export class ErrorState {
  readonly title = input<string>('Something went wrong.');
  readonly message = input<string>('We were unable to load this information.');
  readonly retry = output<void>();

  protected onRetry(): void {
    this.retry.emit();
  }
}
