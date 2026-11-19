import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ResourceEligibilityService } from '../services/resource-eligibility.service';
@Component({
  selector: 'app-resource-eligibility',
  imports: [CommonModule,FormsModule],
  templateUrl: './resource-eligibility.component.html',
  styleUrl: './resource-eligibility.component.css'
})
export class ResourceEligibilityComponent {

projectId: number | null = null;
 project: any = null;
 eligibility: any = null;
 loading = false;
 errorMessage = '';
  projects: any;


  constructor(
    private route: ActivatedRoute,
    private eligibilityService: ResourceEligibilityService
  ) {}

  ngOnInit(): void {
    this.loadProjects();
  }

  // Load project list
  loadProjects(): void {
    this.loading = true;
    this.errorMessage = '';

    //  projects api
    this.eligibilityService.getActiveProjects().subscribe({
      next: (response: any) => {
        this.projects = response.data || [];
        this.loading = false;
      },
      error: (error: any) => {
        console.error('Error loading projects:', error);
        this.errorMessage = 'Unable to load projects.';
        this.loading = false;
      }
    });
  }

  //  dropdown selection changes
  onProjectChange(projectId: number | null): void {
    this.projectId = projectId;
    this.project = null;
    this.eligibility = null;
    this.errorMessage = '';

    if (!projectId || projectId <= 0) {
      return;
    }

    this.loadEligibility();
  }

  loadEligibility(): void {
    this.loading = true;
    this.errorMessage = '';
     const id = this.projectId;
      if (id === null || id <= 0) {
        this.errorMessage = 'Please select a project.';
        return;
      }

    this.eligibilityService.getProjectById(id)
      .subscribe({
        next: (response) => {
          this.project = response.data ?? response;

          this.eligibilityService
            .checkProjectEligibility(id)
            .subscribe({
              next: (result) => {
                this.eligibility = result.data ?? result;
                this.loading = false;
              },
              error: (error) => {
                console.error('Eligibility API error:', error);
                this.errorMessage =
                  error.error?.message ||
                  'Unable to load eligibility results.';
                this.loading = false;
              }
            });
        },
        error: (error) => {
          console.error('Project API error:', error);
          this.errorMessage =
            error.error?.message || 'Unable to load project details.';
          this.loading = false;
        }
      });
  }

  getList(key: string): any[] {
    const list = this.eligibility?.[key];
    return Array.isArray(list) ? list : [];
  }

  refreshEligibility(): void {
    this.loadEligibility();
  }

}
