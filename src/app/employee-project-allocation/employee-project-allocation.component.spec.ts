import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EmployeeProjectAllocationComponent } from './employee-project-allocation.component';

describe('EmployeeProjectAllocationComponent', () => {
  let component: EmployeeProjectAllocationComponent;
  let fixture: ComponentFixture<EmployeeProjectAllocationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [EmployeeProjectAllocationComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EmployeeProjectAllocationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
