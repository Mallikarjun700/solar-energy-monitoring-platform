import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DashboardKpiCardsComponent } from './dashboard-kpi-cards';

describe('DashboardKpiCardsComponent', () => {
  let fixture: ComponentFixture<DashboardKpiCardsComponent>;
  let component: DashboardKpiCardsComponent;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DashboardKpiCardsComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(DashboardKpiCardsComponent);
    component = fixture.componentInstance;

    fixture.componentRef.setInput('kpis', {
      totalPlants: 3,
      totalDevices: 12,
      activeDevices: 10,
      currentPowerKw: 427.5,
      todayEnergyKwh: null,
    });

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should display KPI values', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Total Plants');
    expect(text).toContain('3');
    expect(text).toContain('Total Devices');
    expect(text).toContain('12');
    expect(text).toContain('Active Devices');
    expect(text).toContain('10');
    expect(text).toContain('427.5 kW');
  });

  it('should display an em dash when a KPI is unavailable', () => {
    fixture.componentRef.setInput('kpis', {
      totalPlants: 3,
      totalDevices: 12,
      activeDevices: 10,
      currentPowerKw: null,
      todayEnergyKwh: null,
    });

    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;

    expect(text).toContain('—');
    expect(text).not.toContain('null');
    expect(text).not.toContain('undefined');
  });

  it('should format energy in kWh when available', () => {
    expect(component.formatEnergy(1250.5)).toBe('1,250.5 kWh');
  });
});
