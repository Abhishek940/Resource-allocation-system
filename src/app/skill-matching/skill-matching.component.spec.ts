import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SkillMatchingComponent } from './skill-matching.component';

describe('SkillMatchingComponent', () => {
  let component: SkillMatchingComponent;
  let fixture: ComponentFixture<SkillMatchingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SkillMatchingComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SkillMatchingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
