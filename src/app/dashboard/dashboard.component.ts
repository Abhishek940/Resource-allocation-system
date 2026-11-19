
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { ResourceEligibilityService } from '../services/resource-eligibility.service';
import { ProjectService } from '../services/project.service';
import { EmployeeService } from '../services/employee.service';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.css'
})
export class DashboardComponent implements OnInit {

  loading = true;
  loadingActiveProjects = false;
  showActiveProjectModal = false;
  activeProjectsList: any[] = [];
  loadingBenchResources = false;
  showBenchResourceModal = false;
  benchResourceslist: any[] = [];
  // Recommendation modal
  showRecommendationModal = false;
  loadingRecommendations = false;
  selectedProject: any = null;
  recommendationResult: any = null;
  candidates: any[] = [];
  filteredCandidates: any[] = [];
  selectedCandidate: any = null;
  // Filters
  searchText = '';
  skillFilter = '';
  locationFilter = '';
  categoryFilter = 'All';
  // Candidate comparison
  comparisonCandidates: any[] = [];
  readonly maxComparison = 3;
  // Project-wise eligible count
  projectEligibleCounts: Record<number, number> = {};
  projectMatchLoading: Record<number, boolean> = {};
  // Employee detail modal
showEmployeeDetailModal = false;
loadingEmployeeDetails = false;
employeeDetails: any = null;
showMatchingResourcesModal = false;
matchingResourcesLoading = false;
matchingResources:any=[];
showStrongMatchModel=false;
strongMatchCandidates:any[]=[];
upskillingModel =false;
upskillingCandidate:any[]=[];

  states = {
    activeProjects: 0,
    totalBenchResources: 0,
    matchingResources: 0,
    strongMatchCandidates: 0,
    upskillingRequired: 0,
    frequentSkillsCount: 0
  };

  constructor(
    private projectService: ProjectService,
    private employeeService: EmployeeService,
    private eligibilityService: ResourceEligibilityService
  ) {}

  ngOnInit(): void {
    this.loadDashboardStats();
  }


  loadDashboardStats(): void {
    this.loading = true;
    this.projectService.getDashboardCounts().subscribe({
      next: (res: any) => {
        const data = res?.data ?? {};
        this.states = {
          activeProjects: Number(data.totalActiveProjects ?? 0),
          totalBenchResources: Number(data.totalBenchResources ?? 0),
          matchingResources: Number(data.resourcesMatchingRequirements ?? 0),
          strongMatchCandidates: Number(data.strongMatchCandidates ?? 0),
          upskillingRequired: Number(data.candidatesRequiringUpskilling ?? 0),
          frequentSkillsCount: Number(data.frequentlyRequestedSkills ?? 0)
        };

        this.loading = false;
      },
      error: (err: any) => {
        console.error('Dashboard stats error:', err);
        this.loading = false;
      }
    });
  }

  // active projects

  activeProcjects(): void {
    this.showActiveProjectModal = true;
    this.loadingActiveProjects = true;
    this.activeProjectsList = [];

    // Request more than the default 10 records.
    this.projectService.getProjects(1, 1000).subscribe({
      next: (res: any) => {
        const all = this.extractList(res);
        this.activeProjectsList = all.filter((project: any) =>
          Number(project.status) === 2 &&
          Number(project.deleted_at ?? 0) === 0
        );

        this.loadingActiveProjects = false;
        // Fetch eligible counts for active projects.
        this.loadProjectEligibleCounts();
      },
      error: (err: any) => {
        console.error('Active projects error:', err);
        this.activeProjectsList = [];
        this.loadingActiveProjects = false;
      }
    });
  }

  closeActiveProjectModal(): void {
    this.showActiveProjectModal = false;
  }

  private loadProjectEligibleCounts(): void {
    if (!this.activeProjectsList.length) {
      return;
    }
    const requests = this.activeProjectsList.map((project: any) => {
      const id = Number(project.id);
      this.projectMatchLoading[id] = true;
      return this.eligibilityService.getResourceMatches(id).pipe(
        catchError((err: any) => {
          console.error(`Matching count failed for project ${id}:`, err);
          return of(null);
        })
      );
    });

    forkJoin(requests).subscribe((responses: any[]) => {
      responses.forEach((response: any, index: number) => {
        const projectId = Number(this.activeProjectsList[index].id);
        const result = this.extractMatchingResult(response);

        this.projectEligibleCounts[projectId] =
          Number(result?.eligibleMatches ?? 0);

        this.projectMatchLoading[projectId] = false;
      });
    });
  }

  
  // bench resources
  benchResources(): void {
    this.showBenchResourceModal = true;
    this.loadingBenchResources = true;
    this.benchResourceslist = [];
    this.employeeService.getAllEmployees().subscribe({
      next: (res: any) => {
        const all = this.extractList(res);
        this.benchResourceslist = all.filter((employee: any) =>
          String(employee.resourceStatus ?? '').toLowerCase() === 'bench' &&
          Number(employee.deleted_at ?? 0) === 0
        );

        this.loadingBenchResources = false;
      },
      error: (err: any) => {
        console.error('Bench resources error:', err);
        this.benchResourceslist = [];
        this.loadingBenchResources = false;
      }
    });
  }

  closeBenchResourceModal(): void {
    this.showBenchResourceModal = false;
  }


  // resources recommendations
  openRecommendations(project: any): void {
    this.selectedProject = project;
    this.showRecommendationModal = true;
    this.loadingRecommendations = true;
    this.recommendationResult = null;
    this.candidates = [];
    this.filteredCandidates = [];
    this.selectedCandidate = null;
    this.comparisonCandidates = [];
    this.resetFilters();
    this.eligibilityService.getResourceMatches(Number(project.id)).subscribe({
      next: (res: any) => {
        const result = this.extractMatchingResult(res);
        this.recommendationResult = result;
        // eligibleMatches / partialMatches counts.
        const candidateList = Array.isArray(result?.data) ? result.data : [];

        this.candidates = candidateList
          .map((candidate: any, index: number) =>
            this.normalizeCandidate(candidate, index)
          )
          .sort((a: any, b: any) =>
            b.suitabilityScore - a.suitabilityScore
          );
        this.applyFilters();
        this.loadingRecommendations = false;
        this.projectEligibleCounts[Number(project.id)] =
          Number(result?.eligibleMatches ?? 0);
      },
      error: (err: any) => {
        console.error('Resource matching error:', err);
        this.recommendationResult = null;
        this.candidates = [];
        this.filteredCandidates = [];
        this.loadingRecommendations = false;
      }
    });
  }

  closeRecommendationModal(): void {
    this.showRecommendationModal = false;
    this.selectedProject = null;
    this.selectedCandidate = null;
  }

  private extractMatchingResult(response: any): any {
    // { data: { eligibleMatches, data: [] } }
    // and { eligibleMatches, data: [] }
    return response?.data?.data &&
      !Array.isArray(response.data.data)
      ? response.data.data
      : response?.data ?? response ?? {};
  }

  private normalizeCandidate(candidate: any, index: number): any {
    const employee = candidate?.employee ?? candidate ?? {};
    const skillAnalysis = candidate?.skillAnalysis ?? {};
    const scores = candidate?.scores ?? {};

    const score = Number(
      candidate?.suitabilityScore ??
      candidate?.matchScore ??
      candidate?.score ??
      0
    );

    return {
      ...candidate,

      employeeId: employee.employeeId ?? employee.id ?? candidate.employeeId,
      employeeCode: employee.employeeCode ?? employee.employee_code ?? candidate.employeeCode ?? '-',
      employeeName: employee.employeeName ?? employee.name ?? candidate.employeeName ?? 'Unknown',
      employeeDesignation: employee.employeeDesignation ?? employee.jobTitle ?? '-',
      department: employee.department ?? '-',
      location: employee.location ?? '-',
      experience: Number(employee.employeeExperience ?? employee.totalExperience ?? 0),

      rank: Number(candidate.rank ?? index + 1),
      suitabilityScore: score,

      category: candidate.category ?? candidate.resourceCategory ?? 'Unclassified',
      availabilityStatus: candidate.availabilityStatus ?? candidate.availability ?? '-',

      matchedMandatorySkills:
        skillAnalysis.matchedMandatorySkills ??
        candidate.matchedMandatorySkills ?? [],

      matchedPreferredSkills:
        skillAnalysis.matchedPreferredSkills ??
        candidate.matchedPreferredSkills ?? [],

      missingMandatorySkills:
        skillAnalysis.missingMandatorySkills ??
        candidate.missingMandatorySkills ?? [],

      transferableSkills:
        skillAnalysis.transferableSkills ??
        candidate.transferableSkills ?? [],

      proficiencyScore: Number(scores.proficiency ?? 0),
      explanation: candidate.explanation ?? '',
      reasons: candidate.reasons ?? []
    };
  }

  // for transferable skill
  getTransferableSkills(skills: any[]): string {
  if (!Array.isArray(skills) || !skills.length) {
    return '';
  }
  return skills.map(skill =>
    `${skill.requiredSkill} (via ${skill.relatedEmployeeSkill}) - ${skill.note}`
  ).join('; ');
}

  // search
  applyFilters(): void {
    const search = this.searchText.trim().toLowerCase();
    const skill = this.skillFilter.trim().toLowerCase();
    const location = this.locationFilter.trim().toLowerCase();
    this.filteredCandidates = this.candidates.filter((candidate: any) => {
      const searchableText = [
        candidate.employeeName,
        candidate.employeeCode,
        candidate.assignedRole ?? candidate.employeeDesignation,
      ].join(' ').toLowerCase();
      const matchesSearch = !search || searchableText.includes(search);
      const allSkills = [
        ...this.toArray(candidate.matchedMandatorySkills),
        ...this.toArray(candidate.matchedPreferredSkills),
        ...this.toArray(candidate.transferableSkills)
      ].join(' ').toLowerCase();
      const matchesSkill = !skill || allSkills.includes(skill);
      const matchesLocation = !location ||
        String(candidate.location).toLowerCase().includes(location);
      const matchesCategory = this.categoryFilter === 'All' || String(candidate.category).toLowerCase() ===
               this.categoryFilter.toLowerCase();
      return matchesSearch &&
        matchesSkill &&
        matchesLocation &&
        matchesCategory;
    });
  }

  resetFilters(): void {
    this.searchText = '';
    this.skillFilter = '';
    this.locationFilter = '';
    this.categoryFilter = 'All';
    this.applyFilters();
  }

  
  // resources details
  viewCandidate(candidate: any): void {
    this.selectedCandidate = candidate;
  }


viewEmployeeDetails(candidate: any): void {
  const employeeId = Number(
    candidate?.employeeId ??
    candidate?.employee?.id ??
    candidate?.id
  );

  console.log('Selected candidate:', candidate);
  console.log('Employee Id:', employeeId);

  if (!employeeId) {
    console.error('Employee Id is missing');
    return;
  }

  // Open modal 
  this.showEmployeeDetailModal = true;
  this.loadingEmployeeDetails = true;
  this.employeeDetails = null;
  this.employeeService.getEmployeeById(employeeId).subscribe({
    next: (res: any) => {
      this.employeeDetails = res?.data ?? res;
      this.loadingEmployeeDetails = false;
    },
    error: (err: any) => {
      console.error('Employee API error:', err);
      this.loadingEmployeeDetails = false;
    }
  });
}

closeEmployeeDetailModal(): void {
  this.showEmployeeDetailModal = false;
  this.employeeDetails = null;
}

  closeCandidateDetails(): void {
    this.selectedCandidate = null;
  }

  // comparison

  isSelectedForComparison(candidate: any): boolean {
    return this.comparisonCandidates.some(
      (item: any) => item.employeeId === candidate.employeeId
    );
  }

  Comparison(candidate: any): void {
    if (this.isSelectedForComparison(candidate)) {
      this.comparisonCandidates = this.comparisonCandidates.filter(
          (item: any) => item.employeeId !== candidate.employeeId
        );
      return;
    }

    if (this.comparisonCandidates.length >= this.maxComparison) {
      alert('You can compare a maximum of 3 candidates.');
      return;
    }

    this.comparisonCandidates = [
      ...this.comparisonCandidates,
      candidate
    ];
  }

  clearComparison(): void {
    this.comparisonCandidates = [];
  }


  // export csv
   exportCandidates(): void {
    if (!this.filteredCandidates.length) {
      alert('No candidates available to export.');
      return;
    }

    const headers = [
      'Rank',
      'Employee Code',
      'Employee Name',
      'employeeDesignation',
      'Location',
      'Experience',
      'Match Score',
      'Category',
      'Availability',
      'Matched Mandatory Skills',
      'Missing Mandatory Skills',
      'Transferable Skills',
      'Explanation'
    ];

    const rows = this.filteredCandidates.map((candidate: any) => [
      candidate.rank,
      candidate.employeeCode,
      candidate.employeeName,
      candidate.assignedRole ?? candidate.employeeDesignation,
      candidate.location,
      candidate.experience,
      candidate.suitabilityScore,
      candidate.category,
      candidate.availabilityStatus,
      this.toArray(candidate.matchedMandatorySkills).join(', '),
      this.toArray(candidate.missingMandatorySkills).join(', '),
     // this.toArray(candidate.transferableSkills).join(', '),
      this.getTransferableSkills(candidate.transferableSkills),
      candidate.explanation
    ]);

    const csv = [headers, ...rows]
      .map((row: any[]) =>
        row.map((value: any) =>
          `"${String(value ?? '').replace(/"/g, '""')}"`
        ).join(',')
      )
      .join('\r\n');

    const blob = new Blob(['\uFEFF' + csv], {
      type: 'text/csv;charset=utf-8;'
    });

    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download =
      `resource-recommendations-${this.selectedProject?.projectCode ?? 'project'}.csv`;

    link.click();
    window.URL.revokeObjectURL(url);
  }


// EXPORT TO EXCEL
exportCandidatesExcel(): void {
 if (!this.filteredCandidates.length) {
    alert('No candidates available to export.');
    return;
  }

  const headers = [
    'Rank',
    'Employee Code',
    'Employee Name',
    'Designation',
    'Location',
    'Experience',
    'Match Score',
    'Category',
    'Availability',
    'Matched Mandatory Skills',
    'Missing Mandatory Skills',
    'Transferable Skills',
    'Explanation'
  ];

  const rows = this.filteredCandidates.map((candidate: any) => ({
    'Rank': candidate.rank ?? '',
    'Employee Code': candidate.employeeCode ?? '',
    'Employee Name': candidate.employeeName ?? '',
    'Designation':candidate.assignedRole ?? candidate.employeeDesignation,
    'Location': candidate.location ?? '',
    'Experience': candidate.experience ?? '',
    'Match Score': candidate.suitabilityScore ?? '',
    'Category': candidate.category ?? '',
    'Availability': candidate.availabilityStatus ?? '',
    'Matched Mandatory Skills':
      this.toArray(candidate.matchedMandatorySkills).join(', '),
    'Missing Mandatory Skills':
      this.toArray(candidate.missingMandatorySkills).join(', '),
    'Transferable Skills':
      this.getTransferableSkills(candidate.transferableSkills),
    'Explanation': candidate.explanation ?? ''
  }));

  // Create Excel worksheet
  const worksheet = XLSX.utils.json_to_sheet(rows, {
    header: headers
  });

  // Set column widths
  worksheet['!cols'] = [
    { wch: 10 },
    { wch: 18 },
    { wch: 25 },
    { wch: 20 },
    { wch: 18 },
    { wch: 12 },
    { wch: 15 },
    { wch: 25 },
    { wch: 22 },
    { wch: 35 },
    { wch: 35 },
    { wch: 65 },
    { wch: 100 }
  ];

  // Create workbook
  const workbook = XLSX.utils.book_new();

  XLSX.utils.book_append_sheet(
    workbook,
    worksheet,
    'Resource Recommendations'
  );

  const projectCode =
    this.selectedProject?.projectCode ?? 'project';

  // Download Excel file
  XLSX.writeFile(
    workbook,
    `resource-recommendations-${projectCode}.xlsx`
  );
}

// export to pdf

exportCandidatesPDF(): void {

  if (!this.filteredCandidates.length) {
    alert('No candidates available to export.');
    return;
  }

  const headers = [
    'Rank',
    'Employee Code',
    'Employee Name',
    'Employee Designation',
    'Location',
    'Experience',
    'Match Score',
    'Category',
    'Availability',
    'Matched Mandatory Skills',
    'Missing Mandatory Skills',
    'Transferable Skills',
    'Explanation'
  ];

  // Same data mapping as your CSV export
  const rows = this.filteredCandidates.map((candidate: any) => [
    candidate.rank,
    candidate.employeeCode,
    candidate.employeeName,
    candidate.assignedRole ?? candidate.employeeDesignation,
    candidate.location,
    candidate.experience,
    candidate.suitabilityScore,
    candidate.category,
    candidate.availabilityStatus,
    this.toArray(candidate.matchedMandatorySkills).join(', '),
    this.toArray(candidate.missingMandatorySkills).join(', '),
    //this.toArray(candidate.transferableSkills).join(', '),
    this.getTransferableSkills(candidate.transferableSkills),
    candidate.explanation
  ]);

  const doc = new jsPDF({
    orientation: 'landscape',
    unit: 'mm',
    format: 'a3'
  });

  const projectCode =
    this.selectedProject?.projectCode ?? 'project';

  doc.setFontSize(16);
  doc.text('Resource Recommendation Report', 10, 15);

  doc.setFontSize(10);
  doc.text(`Project: ${projectCode}`, 10, 22);
  doc.text(`Generated: ${new Date().toLocaleDateString()}`, 10, 28);

  autoTable(doc, {
    head: [headers],
    body: rows.map(row =>
      row.map(value => String(value ?? ''))
    ),
    startY: 34,
    theme: 'grid',
    styles: {
      fontSize: 6,
      cellPadding: 1.5,
      overflow: 'linebreak',
      valign: 'top'
    },
    headStyles: {
      fillColor: [41, 65, 105],
      textColor: 255,
      fontStyle: 'bold',
      fontSize: 6
    },
    margin: {
      top: 34,
      left: 8,
      right: 8,
      bottom: 10
    },
    showHead: 'everyPage'
  });

  doc.save(`resource-recommendations-${projectCode}.pdf`);
}

  // helpers

  private extractList(response: any): any[] {
    if (Array.isArray(response?.data)) {
      return response.data;
    }

    if (Array.isArray(response?.data?.data)) {
      return response.data.data;
    }

    return [];
  }

  toArray(value: any): any[] {
    if (Array.isArray(value)) {
      return value;
    }

    if (typeof value === 'string' && value.trim()) {
      try {
        const parsed = JSON.parse(value);
        return Array.isArray(parsed)
          ? parsed
          : value.split(',').map(item => item.trim()).filter(Boolean);
      } catch {
        return value.split(',').map(item => item.trim()).filter(Boolean);
      }
    }

    return [];
  }

  displaySkills(value: any): string {
    return this.toArray(value).join(', ') || 'None';
  }

  getCategoryClass(category: string): string {
    const value = String(category ?? '').toLowerCase();

    if (value.includes('eligible')) return 'status-eligible';
    if (value.includes('partial')) return 'status-partial';
    if (value.includes('future')) return 'status-future';
    if (value.includes('conflict')) return 'status-conflict';
    if (value.includes('excluded')) return 'status-excluded';

    return 'status-default';
  }

  getMatchClass(score: number): string {
    if (score >= 80) return 'match-high';
    if (score >= 60) return 'match-medium';
    return 'match-low';
  }

  getLastMatchingRun(): string {
    const value =
      this.recommendationResult?.lastMatchingRun ??
      this.recommendationResult?.last_matching_run ??
      this.recommendationResult?.completedAt ??
      this.recommendationResult?.completed_at;

    return value ? new Date(value).toLocaleString() : '--';
  }

  // matching resources
  openMatchingResourcesModal(): void {
  this.showMatchingResourcesModal = true;
  this.matchingResourcesLoading = true;
  this.projectService.getMatchingResources().subscribe({
    next: (response: any) => {
      this.matchingResources =
        response.data?.resources ?? [];

      this.matchingResourcesLoading = false;
    },

    error: (error: any) => {

      console.error('Matching resources error:', error);

      this.matchingResources = [];
      this.matchingResourcesLoading = false;
    }

  });
}

closeMatchingResourcesModal(): void {
  this.showMatchingResourcesModal = false;
}

openstrongMatchModel(){
  this.showStrongMatchModel=true;
  this.loading=true;
  
  this.projectService.getstrongMatchCandidate().subscribe({
      next:(res:any)=>{
        this.strongMatchCandidates = res.data?.resources ?? [];
        this.loading = false;
      },
      error:(error:any)=>{
        console.log('!Error',error);
        this.strongMatchCandidates = [];
        this.loading = false;
      }
  })
}

closeStrongskillModel(){
  this.showStrongMatchModel = false;
}



showUpskillingModel = false;
upskillingResourcesLoading = false;
upskillingCandidates: any[] = [];

openUpskillingModel(): void {
  this.showUpskillingModel = true;
  this.getUpskillingCandidates();
}

closeUpskillingModel(): void {
  this.showUpskillingModel = false;
}

getUpskillingCandidates(): void {
  this.upskillingResourcesLoading = true;

  this.projectService.getUpskillingCandidates().subscribe({
    next: (res: any) => {
      this.upskillingCandidates = res?.data?.resources ?? [];
      this.upskillingResourcesLoading = false;
    },
    error: (error: any) => {
      console.error('Error loading upskilling candidates:', error);
      this.upskillingCandidates = [];
      this.upskillingResourcesLoading = false;
    }
  });
}





}
