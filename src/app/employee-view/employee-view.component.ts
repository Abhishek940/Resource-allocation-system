import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, FormsModule } from '@angular/forms';
import Swal from 'sweetalert2';
import { EmployeeService } from '../services/employee.service';

@Component({
  selector: 'app-employee-view',
  imports: [CommonModule, RouterLink, FormsModule, ReactiveFormsModule],
  templateUrl: './employee-view.component.html',
  styleUrl: './employee-view.component.css'
})
export class EmployeeViewComponent implements OnInit {

  employeeList: any[] = [];
  paginatedEmployeeList: any[] = [];
  isLoading: boolean = false;

  // Pagination Properties
  currentPage: number = 1;
  pageSize: number = 5;
  totalPages: number = 1;
  totalRecords: number = 0;
  pageSizes: number[] = [5, 10, 25, 50];

  // Edit Modal State
  isEditModalOpen: boolean = false;
  editEmployeeForm!: FormGroup;
  selectedEmployeeId: number | null = null;
  selectedEmployeeCode: string = '';

  departments = [
    'Engineering & Product',
    'Data Science & AI',
    'Cloud & DevOps',
    'Quality Assurance (QA)',
    'Cybersecurity',
    'Enterprise Solutions',
    'Consulting & Delivery'
  ];

  locations = [
    'Bangalore',
    'Hyderabad',
    'Pune',
    'Noida',
    'Mumbai',
    'Chennai',
    'Patna',
    'Remote'
  ];

  proficiencyLevels = [
    { value: '1', label: '1 - Beginner (Foundational Knowledge)' },
    { value: '2', label: '2 - Intermediate (Practical Working Knowledge)' },
    { value: '3', label: '3 - Proficient (Autonomous & Independent)' },
    { value: '4', label: '4 - Advanced (Subject Matter Expert)' },
    { value: '5', label: '5 - Expert (Industry Leader / Architect)' }
  ];

  resourceStatuses = [
    'Bench',
    'Allocated',
    'Partially Available',
    'Unavailable'
  ];

  validationStatuses = [
    'Self-Reported',
    'Manager-Validated',
    'Assessment-Verified'
  ];
role:string ='';
isViewModalOpen = false;
selectedEmployee: any = null;

  constructor(
    private router: Router,
    private fb: FormBuilder,
    private employeeService: EmployeeService
  ) { }

  ngOnInit(): void {
    this.initEditForm();
    this.getEmployees();
    this.role = localStorage.getItem('role') || '';
    console.log('roles:',this.role);
  }

  initEditForm(): void {
    this.editEmployeeForm = this.fb.group({
      employeeName: ['', [Validators.required]],
      designation: ['', [Validators.required]],
      department: ['', [Validators.required]],
      location: ['', [Validators.required]],
      primaryskill: ['', [Validators.required]],
      proficiency: ['', [Validators.required]],
      experience: ['', [Validators.required]],
      secondarySkills: [''],
      tertiarySkills: [''],
      assessmentScore: [''],
      validationStatus: ['', [Validators.required]],
      certifications: [''],
      resourceStatus: ['', [Validators.required]],
      availableFrom: [''],
      projectExperience: ['']
    });
  }

  goBack(): void {
    this.router.navigate(['/dashboard']);
  }

  getEmployees(): void {
    this.isLoading = true;
    this.employeeService.getEmployees().subscribe({
      next: (res: any) => {
        let data = [];
        if (Array.isArray(res.data)) {
          data = res.data;
        } else if (res.data && Array.isArray(res.data.data)) {
          data = res.data.data;
        } else if (Array.isArray(res)) {
          data = res;
        }
        this.employeeList = data;
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
    this.totalRecords = this.employeeList.length;
    this.totalPages = Math.ceil(this.totalRecords / this.pageSize) || 1;

    if (this.currentPage > this.totalPages) {
      this.currentPage = this.totalPages;
    }
    if (this.currentPage < 1) {
      this.currentPage = 1;
    }

    const startIndex = (this.currentPage - 1) * this.pageSize;
    const endIndex = startIndex + Number(this.pageSize);
    this.paginatedEmployeeList = this.employeeList.slice(startIndex, endIndex);
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

     if (data.department && !this.departments.includes(data.department)) {
      this.departments.push(data.department);
    }
    if (data.location && !this.locations.includes(data.location)) {
      this.locations.push(data.location);
    }
    if (data.resourceStatus && !this.resourceStatuses.includes(data.resourceStatus)) {
      this.resourceStatuses.push(data.resourceStatus);
    }
    if (data.validationStatus && !this.validationStatuses.includes(data.validationStatus)) {
      this.validationStatuses.push(data.validationStatus);
    }

    this.selectedEmployeeCode = data.employeeCode || `EMP-${data.id}`;

    this.editEmployeeForm.patchValue({
      employeeName: data.employeeName || '',
      designation: data.designation || '',
      department: data.department || '',
      location: data.location || '',
      primaryskill: data.primaryskill || '',
      proficiency: data.proficiency !== undefined && data.proficiency !== null ? String(data.proficiency) : '',
      experience: data.experience || '',
      secondarySkills: data.secondarySkills || '',
      tertiarySkills: data.tertiarySkills || '',
      assessmentScore: data.assessmentScore || '',
      validationStatus: data.validationStatus || '',
      certifications: data.certifications || '',
      resourceStatus: data.resourceStatus || '',
      availableFrom: this.formatDateForInput(data.availableFrom),
      projectExperience: data.projectExperience || ''
    });
  }

  // Edit Modal 
  openEditModal(emp: any): void {
    this.selectedEmployeeId = emp.id;
    this.populateEditForm(emp);
    this.isEditModalOpen = true;

     this.employeeService.getEmployeeById(emp.id).subscribe({
      next: (res: any) => {
        const fullData = res.data || res;
        if (fullData) {
          this.populateEditForm(fullData);
        }
      },
      error: (err: any) => {
        console.warn('API returned fallback, row data already populated:', err);
      }
    });
  }

  // Close Edit Modal
  closeEditModal(): void {
    this.isEditModalOpen = false;
    this.selectedEmployeeId = null;
    this.selectedEmployeeCode = '';
    this.editEmployeeForm.reset();
  }

  //  Update
  updateEmployee(): void {
    if (this.editEmployeeForm.invalid) {
      this.editEmployeeForm.markAllAsTouched();
      Swal.fire({
        icon: 'warning',
        title: 'Required Fields Missing',
        text: 'Please check and fill in all mandatory fields.'
      });
      return;
    }

    if (!this.selectedEmployeeId) return;

    this.employeeService.updateEmployee(this.selectedEmployeeId, this.editEmployeeForm.value).subscribe({
      next: (res: any) => {
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: res.message || 'Employee updated successfully.',
          confirmButtonText:'ok',
          showConfirmButton: true,
        });
        this.closeEditModal();
        this.getEmployees();
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

  // Delete Employee
  deleteEmployee(id: number): void {
    Swal.fire({
      title: 'Are you sure?',
      text: 'Do you really want to delete this employee?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete it!'
     //showConfirmButton: true,
    }).then((result) => {
      if (result.isConfirmed) {
        this.employeeService.deleteEmployee(id).subscribe({
          next: (res: any) => {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: res.message || 'Employee deleted successfully.',
              confirmButtonText:'ok',
              showConfirmButton: true
            });
            this.getEmployees();
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

  // Status badge class helper
  getStatusBadgeClass(status: string): string {
    switch (status) {
      case 'Allocated':
        return 'badge-allocated';
      case 'Bench':
        return 'badge-bench';
      case 'Partially Available':
        return 'badge-partial';
      case 'Unavailable':
        return 'badge-unavailable';
      default:
        return 'badge-default';
    }
  }

openViewModal(employee: any): void {
  this.selectedEmployee = employee;
  this.isViewModalOpen = true;
}

closeViewModal(): void {
  this.isViewModalOpen = false;
  this.selectedEmployee = null;
}

}
