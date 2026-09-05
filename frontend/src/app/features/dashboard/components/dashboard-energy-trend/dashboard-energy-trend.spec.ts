import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DashboardEnergyTrendComponent } from './dashboard-energy-trend';

describe('DashboardEnergyTrendComponent', () => {
  let fixture: ComponentFixture<DashboardEnergyTrendComponent>;
  let component: DashboardEnergyTrendComponent;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DashboardEnergyTrendComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(DashboardEnergyTrendComponent);
    component = fixture.componentInstance;
  });

  it('should create', () => {
    fixture.componentRef.setInput('points', []);
    fixture.detectChanges();

    expect(component).toBeTruthy();
  });

  it('should display energy trend points', () => {
    fixture.componentRef.setInput('points', [
      {
        timestamp: '2026-09-05T10:00:00Z',
        energyKwh: 125.5,
      },
      {
        timestamp: '2026-09-05T11:00:00Z',
        energyKwh: 142.75,
      },
    ]);

    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;

    expect(text).toContain('125.5 kWh');
    expect(text).toContain('142.75 kWh');
  });

  it('should display an empty state when no points exist', () => {
    fixture.componentRef.setInput('points', []);
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain(
      'Energy trend data is currently unavailable.',
    );
  });

  it('should display an em dash for unavailable energy', () => {
    fixture.componentRef.setInput('points', [
      {
        timestamp: '2026-09-05T10:00:00Z',
        energyKwh: null,
      },
    ]);

    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;

    expect(text).toContain('—');
    expect(text).not.toContain('null');
    expect(text).not.toContain('undefined');
  });

  it('should handle invalid timestamps safely', () => {
    expect(component.formatTimestamp('invalid')).toBe('—');
  });

  it('should format energy correctly', () => {
    expect(component.formatEnergy(1250.5)).toBe('1,250.5 kWh');
    expect(component.formatEnergy(null)).toBe('—');
  });
});
