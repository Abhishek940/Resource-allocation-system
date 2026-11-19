
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ResourceEligibilityService } from '../services/resource-eligibility.service';
import { forkJoin, Observable } from 'rxjs';
import { ProjectService } from '../services/project.service';
interface SkillGap {
  skill: string;
  requiredProficiency: number;
  actualProficiency: number | null;
  gap: number | null;
  gapType: string;
  mandatory: boolean;
  preferred: boolean;
  priority: string;
}

interface LearningRecommendation {
  skill: string;
  gapType: string;
  recommendedAction: string;
  validation: string;
  status: string;
}

interface Resource {
  employeeId: number;
  employeeName: string;
  employeeCode: string;
  suitabilityScore: number;
  category: string;
  gapData: any;
  skillGaps: SkillGap[];
}

@Component({
  selector: 'app-skill-gap',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './skill-gap.component.html',
  styleUrl: './skill-gap.component.css'
})
export class SkillGapComponent implements OnInit {

  projects: any[] = [];
  selectedProjectId: number | null = null;
  loading = false;
  resources: Resource[] = [];
  selectedResource: Resource | null = null;
  skillGaps: SkillGap[] = [];
  recommendations: LearningRecommendation[] = [];
  summary: any = {};
 selectedProject: any = null; 

  constructor(
    private eligibilityService: ResourceEligibilityService,
    private ProjectService:ProjectService,

  ) {}

  ngOnInit(): void {
    this.getProjects();
  }

  // Load projects
  getProjects(): void {
    this.ProjectService.getProjects().subscribe({
      next: (res: any) => {
        this.projects = res?.data?.data ?? res?.data ?? [];
      },
      error: (error: any) => {
       console.error('Project loading error:', error);
      }
    });

  }
  
  onProjectChange(event: Event): void {
    const projectId = Number((event.target as HTMLSelectElement).value);
    this.selectedProjectId = projectId || null;
    if (this.selectedProjectId) {
    this.selectedProject = this.projects.find(p => p.id === this.selectedProjectId) || null;
   } else {
    this.selectedProject = null;
  }
    // reset
    this.resources = [];
    this.skillGaps = [];
    this.recommendations = [];
    this.selectedResource = null;
    this.summary = {};

    if (projectId > 0) {
      this.loadProjectResources(projectId);
    }

  }

  // resources and skill gaps
 
loadProjectResources(projectId: number): void {
  this.loading = true;
  this.eligibilityService.checkProjectEligibility(projectId)
    .subscribe({
      next: (res: any) => {
        const data = res?.data;
       // Combine eligible and future availability employees
        const matches = [
          ...(data?.eligible ?? []),
          ...(data?.partialAvailability ?? []),
          ...(data?.futureAvailability ?? [])
        ];

        if (!matches.length) {
          this.resources = [];
          this.loading = false;
          return;
        }

        //  skill gap API for each employee
        const requests: Observable<any>[] = matches.map(
          (item: any) => {
            return this.eligibilityService.skillGapAnalysis(
              projectId,
              Number(item.employeeId)
            );
          }
        );

        forkJoin(requests).subscribe({
          next: (responses: any[]) => {

            this.resources = matches.map(
              (item: any, index: number): Resource => {

                const gapData = responses[index]?.data;

                return {
                  employeeId: item.employeeId,
                  employeeName: item.employeeName,
                  employeeCode: item.employeeCode,
                  suitabilityScore: item.suitabilityScore ?? 0,
                  category: item.resourceStatus ?? '',
                  gapData: gapData,
                  skillGaps: gapData?.gaps ?? []
                };

              }
            );

            this.loading = false;
          },

          error: (error: any) => {
            console.error('Skill gap loading error:', error);
            this.loading = false;
          }
        });

      },

      error: (error: any) => {
        console.error('Eligibility loading error:', error);
        this.loading = false;
      }
    });
}


  // Select individual resource
  selectResource(resource: Resource): void {

  this.selectedResource = resource;
  this.summary = resource.gapData;
  this.skillGaps = resource.skillGaps;

  console.log('All skill gaps:', resource.skillGaps);

  resource.skillGaps.forEach(gap => {
    console.log(
      gap.skill,
      'Mandatory:', gap.mandatory,
      'Gap Type:', gap.gapType
    );
  });

  this.recommendations = this.getRecommendations(
    resource.skillGaps
  );

  console.log('Final recommendations:', this.recommendations);
}

  // learning recommendations
  getRecommendations(gaps: SkillGap[]): LearningRecommendation[] {

    return (gaps || [])
      //  mandatory skills generate recommendations
      .filter(gap =>
          gap.mandatory === true &&(
          gap.gapType === 'Missing' || gap.gapType === 'Underdeveloped'
        )
      )
      .map(gap => {
        const skill = gap.skill.toLowerCase();
        let action = '';
        let validation = '';

        if (skill.includes('mysql')) {
          action = 'Complete SQL exercises';
          validation = 'Practical assignment';
        } else if (skill.includes('docker')) {
          action = 'Complete containerization exercises';
          validation = 'Demonstration and technical review';
        } else if (skill.includes('spring security')) {
          action = 'Complete the approved learning module';
          validation = 'Knowledge assessment';

        } else if (skill.includes('aws')) {
          action = 'Complete an approved AWS fundamentals learning module';
          validation = 'Knowledge assessment';
        } else {
          action = `Identify approved learning resources for ${gap.skill}`;
          validation = 'Knowledge assessment and technical review';

        }

        return {
          skill: gap.skill,
          gapType: gap.gapType,
          recommendedAction: action,
          validation: validation,
          status: 'Pending L&K Review'

        };

      });

  }

  //  css 
  getGapClass(type: string): string {
    switch (type) {
      case 'Matched':
        return 'matched';
      case 'Missing':
        return 'missing';
      case 'Underdeveloped':
        return 'underdeveloped';
      default:
        return '';
    }

  }

}
