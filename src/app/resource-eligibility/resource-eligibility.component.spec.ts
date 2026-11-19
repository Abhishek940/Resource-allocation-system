import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ResourceEligibilityComponent } from './resource-eligibility.component';

describe('ResourceEligibilityComponent', () => {
  let component: ResourceEligibilityComponent;
  let fixture: ComponentFixture<ResourceEligibilityComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ResourceEligibilityComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ResourceEligibilityComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
