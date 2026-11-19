import { TestBed } from '@angular/core/testing';

import { ResourceEligibilityService } from './resource-eligibility.service';

describe('ResourceEligibilityService', () => {
  let service: ResourceEligibilityService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(ResourceEligibilityService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
