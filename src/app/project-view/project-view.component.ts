import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, FormsModule } from '@angular/forms';
import Swal from 'sweetalert2';
import { ProjectService } from '../services/project.service';
@Component({
  selector: 'app-project-view',
  imports: [CommonModule, RouterLink, FormsModule, ReactiveFormsModule],
  templateUrl: './project-view.component.html',
  styleUrl: './project-view.component.css'
})
export class ProjectViewComponent {

 projectList: any[] = [];
  paginatedProjectlist: any[] = [];
  isLoading: boolean = false;

  // Pagination Properties
  currentPage: number = 1;
  pageSize: number = 5;
  totalPages: number = 1;
  totalRecords: number = 0;
  pageSizes: number[] = [5, 10, 25, 50];

  // Edit Modal State
  isEditModalOpen: boolean = false;
  editProjectForm!: FormGroup;
  selectedProjectId: number | null = null;
  selectedProjectCode: string = '';

 
  constructor(
    private router: Router,
    private fb: FormBuilder,
    private projectService: ProjectService
  ) { }

  ngOnInit(): void {
    this.initEditForm();
    this.getProjects();
  }

   businessDomains = [
    'Banking, Financial Services & Insurance (BFSI)',
    'Healthcare & Life Sciences',
    'Retail, E-Commerce & Consumer Goods',
    'Telecommunications & 5G Networks',
    'Cloud Infrastructure & DevOps',
    'Automotive & Smart Mobility',
    'Public Sector & Government Services',
    'Energy, Oil & Utilities',
    'Enterprise Software & SaaS'
  ];

  locations = [
    'Bangalore, India',
    'Hyderabad, India',
    'Pune, India',
    'Noida / Delhi NCR, India',
    'Mumbai, India',
    'Chennai, India',
    'Patna, India',
    'Client Onsite (International)',
    'Remote (Any Location)'
  ];

  workModes = [
    'Patna',
    'Delhi',
    'Bhunaswer',
    'Client Onsite',
    'Remote'
  ];

  duration = [
    '6 Months',
    '1 Year',
    '2 Year',
    '3 Year',
    '4 Year',
    '5 Year'
  ];

  workArrangements = [
    'On-site (Client Office / Campus)',
    'Hybrid (2-3 Days Office / Remote)',
    '100% Remote / Work from Home',
    'As required / Flexible'
  ];

  proficiencyLevels = [
    { value: '1', label: '1 out of 5 (Foundational / Beginner)' },
    { value: '2', label: '2 out of 5 (Intermediate / Working Knowledge)' },
    { value: '3', label: '3 out of 5 (Proficient / Autonomous - Recommended)' },
    { value: '4', label: '4 out of 5 (Advanced / Lead Technical Specialist)' },
    { value: '5', label: '5 out of 5 (Master / Principal Architect)' }
  ];

  availabilityOptions = [
    'Immediate (Within 48 hours)',
    'Within 15 days',
    'Within 30 days',
    'Within 45 days',
    'Within 60 days'
  ];

  durationOptions = [
    '3 months',
    '6 months',
    '9 months',
    '12 months',
    '18 months',
    'Long-term (2+ years)',
    'Indefinite / SOW-Based'
  ];

  priorityLevels = [
    { value: 'Low', label: 'Low', badgeClass: 'badge-low' },
    { value: 'Medium', label: 'Medium', badgeClass: 'badge-medium' },
    { value: 'High', label: 'High', badgeClass: 'badge-high' },
    { value: 'Critical', label: 'Critical', badgeClass: 'badge-critical' }
  ];
  initEditForm(): void {
     this.editProjectForm = this.fb.group({
    projectName: [''],
    project_des: [''],
    businessDomain: [''],
    requirementOwner: [''],
    requirementPriority: [null],
    location: [''],
    duration: [''],
    requiredTechnology: [''],
    mandatorySkills: [''],
    preferredSkills: [''],
    minProficiency: [''],
    minExperience: [''],
    numberOfResources: [1],
    joiningDate: [''],
    availabilityDuration: [''],
    requiredAvailability: [''],
    projectDuration: [''],
    status: [null]
    });
  }

  goBack(): void {
    this.router.navigate(['/dashboard']);
  }

  getProjects(): void {
    this.isLoading = true;
    this.projectService.getProjects().subscribe({
      next: (res: any) => {
        let data = [];
        if (Array.isArray(res.data)) {
          data = res.data;
        } else if (res.data && Array.isArray(res.data.data)) {
          data = res.data.data;
        } else if (Array.isArray(res)) {
          data = res;
        }
        this.projectList = data;
        this.updatePagination();
        this.isLoading = false;
      },
      error: (error: any) => {
        console.error('API Error:', error);
        this.isLoading = false;
      }
    });
  }

  // Pagination 
  updatePagination(): void {
    this.totalRecords = this.projectList.length;
    this.totalPages = Math.ceil(this.totalRecords / this.pageSize) || 1;

    if (this.currentPage > this.totalPages) {
      this.currentPage = this.totalPages;
    }
    if (this.currentPage < 1) {
      this.currentPage = 1;
    }

    const startIndex = (this.currentPage - 1) * this.pageSize;
    const endIndex = startIndex + Number(this.pageSize);
    this.paginatedProjectlist = this.projectList.slice(startIndex, endIndex);
  }

  setPage(page: number): void {
    if (page >= 1 && page <= this.totalPages) {
      this.currentPage = page;
      this.updatePagination();
    }
  }

  onPageSizeChange(): void {
    this.currentPage = 1;
    this.updatePagination();
  }

  getPageNumbers(): number[] {
    const pages: number[] = [];
    for (let i = 1; i <= this.totalPages; i++) {
      pages.push(i);
    }
    return pages;
  }

  getStartIndex(): number {
    return this.totalRecords === 0 ? 0 : (this.currentPage - 1) * this.pageSize + 1;
  }

  getEndIndex(): number {
    return Math.min(this.currentPage * this.pageSize, this.totalRecords);
  }

  formatDateForInput(dateVal: any): string {
    if (!dateVal) return '';
    try {
      const str = String(dateVal).trim();
      if (str.includes('T')) {
        return str.split('T')[0];
      }
      if (str.includes(' ')) {
        return str.split(' ')[0];
      }
      if (str.includes('/')) {
        const parts = str.split('/');
        if (parts.length === 3) {
          // Detect YYYY/MM/DD or DD/MM/YYYY
          if (parts[0].length === 4) {
            return `${parts[0]}-${parts[1].padStart(2, '0')}-${parts[2].padStart(2, '0')}`;
          }
          return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
        }
      }
      return str;
    } catch {
      return '';
    }
  }

  populateEditForm(data: any): void {
    if (!data) return;
    this.selectedProjectCode = data.projectCode || `PR0-${data.id}`;

    this.editProjectForm.patchValue({
      projectName: data.projectName || '',
      project_des: data.project_des || '',
      businessDomain: data.businessDomain || '',
      requirementOwner: data.requirementOwner || '',
      requirementPriority: Number(data.requirementPriority || ''),
      location: data.location || '',
      duration: data.duration || '',
      requiredTechnology: data.requiredTechnology || '',
      mandatorySkills: data.mandatorySkills || '',
      preferredSkills: data.preferredSkills || '',
      minProficiency: data.minProficiency || '',
      minExperience: data.minExperience || '',
      numberOfResources: data.numberOfResources || '',
      joiningDate: this.formatDateForInput(data.joiningDate),
      availabilityDuration: data.availabilityDuration || '',
      requiredAvailability: data.requiredAvailability || '',
      projectDuration: data.projectDuration || '',
      status: data.status || ''
    });
  }

  // Edit Modal 
  openEditModal(emp: any): void {
    console.log('data',emp)
    this.selectedProjectId = emp.id;
    this.populateEditForm(emp);
    this.isEditModalOpen = true;

     this.projectService.getProjectById(emp.id).subscribe({
      next: (res: any) => {
        const fullData = res.data || res;
        if (fullData) {
          this.populateEditForm(fullData);
        }
      },
      error: (err: any) => {
        console.warn('Error:', err);
      }
    });
  }

  // Close Edit Modal
  closeEditModal(): void {
    this.isEditModalOpen = false;
    this.selectedProjectId = null;
    this.selectedProjectCode = '';
    this.editProjectForm.reset();
  }

  //  Update
  updateProject(): void {
    if (this.editProjectForm.invalid) {
      this.editProjectForm.markAllAsTouched();
      Swal.fire({
        icon: 'warning',
        title: 'Required Fields Missing',
        text: 'Please check and fill in all mandatory fields.'
      });
      return;
    }

    if (!this.selectedProjectId) return;

    this.projectService.updateProject(this.selectedProjectId, this.editProjectForm.value).subscribe({
      next: (res: any) => {
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: res.message || 'Project updated successfully.',
          confirmButtonText:'ok',
          showConfirmButton: true,
        });
        this.closeEditModal();
        this.getProjects();
      },
      error: (error: any) => {
        console.error('API Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Update Failed',
          text: error.error?.message || 'Something went wrong while updating the employee.'
        });
      }
    });
  }

  // delete project
  deleteProject(id: number): void {
    Swal.fire({
      title: 'Are you sure?',
      text: 'Do you really want to delete this project?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        this.projectService.deleteProject(id).subscribe({
          next: (res: any) => {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: res.message || 'Project deleted successfully.',
              confirmButtonText:'ok',
              showConfirmButton: true
            });
            this.getProjects();
          },
          error: (error: any) => {
            console.error('Delete Error:', error);
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error.error?.message || 'Failed to delete employee.'
            });
          }
        });
      }
    });
  }

  // Status  class 
 getStatusText(status: number): string {
  switch (status) {
    case 1: return 'Pending';
    case 2: return 'Active';
    case 3: return 'On Hold';
    case 4: return 'Completed';
    case 5: return 'Rejected';
    case 6: return 'Draft';
    default: return 'Unknown';
  }
}

getStatusBadgeClass(status: number): string {
  switch (status) {
    case 1: return 'badge pending';
    case 2: return 'badge success';
    case 3: return 'badge hold';
    case 4: return 'badge success';
    case 5: return 'badge rejected';
    case 6: return 'badge draft';
    default: return 'badge';
  }
}


}


