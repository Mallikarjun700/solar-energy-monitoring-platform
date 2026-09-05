import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DashboardPlantOverviewComponent } from './dashboard-plant-overview';

describe('DashboardPlantOverviewComponent', () => {
  let fixture: ComponentFixture<DashboardPlantOverviewComponent>;
  let component: DashboardPlantOverviewComponent;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DashboardPlantOverviewComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(DashboardPlantOverviewComponent);
    component = fixture.componentInstance;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should display plant information', () => {
    fixture.componentRef.setInput('plants', [
      {
        id: 1,
        name: 'Solar Plant A',
        code: 'SPA-001',
        location: 'Block A',
        capacityKw: 500,
        status: 'active',
        currentPowerKw: 427.5,
        performancePercent: 85.5,
      },
    ]);

    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Solar Plant A');
    expect(text).toContain('SPA-001');
    expect(text).toContain('Block A');
    expect(text).toContain('500 kW');
    expect(text).toContain('427.5 kW');
    expect(text).toContain('85.5%');
    expect(text).toContain('active');
  });

  it('should display an empty state when no plants exist', () => {
    fixture.componentRef.setInput('plants', []);

    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('No plants are available.');
  });

  it('should display unavailable values as an em dash', () => {
    fixture.componentRef.setInput('plants', [
      {
        id: 1,
        name: 'Solar Plant A',
        code: 'SPA-001',
        location: null,
        capacityKw: null,
        status: 'unknown',
        currentPowerKw: null,
        performancePercent: null,
      },
    ]);

    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;

    expect(text).toContain('—');
    expect(text).not.toContain('null');
    expect(text).not.toContain('undefined');
  });
});
