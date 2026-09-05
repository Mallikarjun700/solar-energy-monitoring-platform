import { ChangeDetectionStrategy, Component, OnInit, inject, signal } from '@angular/core';

import { DashboardService } from './services/dashboard.service';
import { DashboardViewModel } from './models/dashboard-view.model';
import { DashboardKpiCardsComponent } from './components/dashboard-kpi-cards/dashboard-kpi-cards';
import { DashboardPlantOverviewComponent } from './components/dashboard-plant-overview/dashboard-plant-overview';
import { DashboardEnergyTrendComponent } from './components/dashboard-energy-trend/dashboard-energy-trend';
import { DashboardAlertsSummaryComponent } from './components/dashboard-alerts-summary/dashboard-alerts-summary';
import { DashboardRecentActivityComponent } from './components/dashboard-recent-activity/dashboard-recent-activity';
import { LoadingState } from '../../shared/components/loading-state/loading-state';
import { ErrorState } from '../../shared/components/error-state/error-state';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [
    DashboardKpiCardsComponent,
    DashboardPlantOverviewComponent,
    DashboardEnergyTrendComponent,
    DashboardAlertsSummaryComponent,
    DashboardRecentActivityComponent,
    LoadingState,
    ErrorState,
  ],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardComponent implements OnInit {
  private readonly dashboardService = inject(DashboardService);

  readonly dashboard = signal<DashboardViewModel | null>(null);
  readonly loading = signal(true);
  readonly error = signal<string | null>(null);

  ngOnInit(): void {
    this.loadDashboard();
  }

  retry(): void {
    this.loadDashboard();
  }

  private loadDashboard(): void {
    this.loading.set(true);
    this.error.set(null);

    this.dashboardService.getDashboard().subscribe({
      next: (dashboard) => {
        this.dashboard.set(dashboard);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load dashboard data.');
        this.loading.set(false);
      },
    });
  }
}
