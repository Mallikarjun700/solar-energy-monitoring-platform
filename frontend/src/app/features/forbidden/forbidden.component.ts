import { Component } from '@angular/core';

@Component({
  selector: 'app-forbidden',
  standalone: true,
  template: `
    <main>
      <h1>Access denied</h1>
      <p>You do not have permission to access this resource.</p>
    </main>
  `,
})
export class ForbiddenComponent {}
