
import { CommonModule } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import {ProjectService} from '../services/project.service'
import { EmployeeService } from '../services/employee.service';
import { ResourceEligibilityService } from '../services/resource-eligibility.service';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import Swal from 'sweetalert2';
import { FormBuilder, FormGroup, Validators ,ReactiveFormsModule} from '@angular/forms';
@Component({
  selector: 'app-employee-project-allocation',
  standalone: true,
 imports: [CommonModule, FormsModule, ReactiveFormsModule, RouterLink],
  templateUrl: './employee-project-allocation.component.html',
  styleUrl: './employee-project-allocation.component.css'
})
export class EmployeeProjectAllocationComponent implements OnInit {
  constructor(
    private route: ActivatedRoute,
    private router:Router,
    private projectService: ProjectService,
    private fb:FormBuilder,
    private employeeService: EmployeeService,
    private resourceEligibilityservice:ResourceEligibilityService
  ) {}
allocationList: any[] = [];
  editingId: number | null = null;
  loading = false;
  employeelist:any[]=[];
 allocationlist:any[]=[];
  projectlist: any[] = [];
  isLoading: boolean = false;
 allocations: any[] = [];
  employees: any[] = [];
  projects: any[] = [];
  projectAllocation!: FormGroup;
  ngOnInit(): void {
  this.getEmployeelist();
  this.getProjectlist();
  this.getProjectAllocationlist();

 this.projectAllocation = this.fb.group({
  employee_id: ['', [Validators.required]],
  project_id: ['', [Validators.required]],
  allocation_start: ['', [Validators.required]],
  allocation_end: [''],
  allocation_percentage: ['', [Validators.required]],
   allocation_status: ['', [Validators.required]],
});

  }
  
  getEmployeelist():void {
    this.employeeService.getDropdownEmployees().subscribe({
      next: (res:any) => {
        console.log('Employee List:', res);
        this.employeelist = res.data || [];
      },
      error: (error:any) => {
        console.error('API Error:', error);
      }
    });
  }

   getProjectlist():void {
    this.projectService.getProjectdropdownlist().subscribe({
      next: (res:any) => {
        this.projectlist = res.data || [];
      },
      error: (error:any) => {
        console.error('API Error:', error);
      }
    });
  }

   getProjectAllocationlist():void {
    this.resourceEligibilityservice.getAllocationlist().subscribe({
      next: (res:any) => {
        this.allocationlist = res.data || [];
      },
      error: (error:any) => {
        console.error('API Error:', error);
      }
    });
  }

  convertDate(date: string): string {
  if (!date) return '';

  const [year, month, day] = date.split('-');
  return `${day}-${month}-${year}`;
}
 
saveAllocation(): void {
  // Check all required fields
  const requiredFields = [
    { name: 'employee_id', label: 'Employee Name' },
    { name: 'project_id', label: 'Project Name' },
    { name: 'allocation_start', label: 'Start Date' },
    { name: 'allocation_end', label: 'End Date' },
    { name: 'allocation_percentage', label: 'Allocation Percentage' },
    { name: 'allocation_status', label: 'Allocation Status' }
  ];

  for (const field of requiredFields) {
    const control = this.projectAllocation.get(field.name);

    if (!control || control.invalid || control.value === null || control.value === '') {
      control?.markAsTouched();

      Swal.fire({
        icon: 'warning',
        title: 'Required',
        text: `${field.label} is required.`
      });

      return;
    }
  }

  const formValue = this.projectAllocation.getRawValue();

  // Validate date range
  if (formValue.allocation_end < formValue.allocation_start) {
    Swal.fire({
      icon: 'warning',
      title: 'Invalid Dates',
      text: 'End Date must be after or equal to Start Date.'
    });
    return;
  }

  // Ensure IDs are numeric
  const payload = {
    ...formValue,
    employee_id: Number(formValue.employee_id),
    project_id: Number(formValue.project_id),
     allocation_start: this.convertDate(formValue.allocation_start),
    allocation_end: formValue.allocation_end
    ? this.convertDate(formValue.allocation_end)
    : null,
    allocation_percentage: Number(formValue.allocation_percentage)
  };

  console.log('Selected project ID:', payload.project_id);
  console.log('Allocation payload:', payload);

  this.resourceEligibilityservice.insertAllocation(payload).subscribe({
    next: (res: any) => {
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: res.message || 'Allocation saved successfully.',
        confirmButtonText: 'OK'
      }).then(() => {
        this.projectAllocation.reset();
        this.router.navigate(['/projects']);
      });
    },
    error: (error: any) => {
      console.error('API Error:', error);

      Swal.fire({
        icon: 'error',
        title: 'Submission Failed',
        text: error.error?.message ||
          'Something went wrong while saving the allocation.'
      });
    }
  });
}

 // Delete 
  deleteAllocation(id: number): void {
    Swal.fire({
      title: 'Are you sure?',
      text: 'Do you really want to delete this Record?',
      icon: 'warning',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete it!',
     // confirmButtonText:'ok',
      showConfirmButton: true,
       showCancelButton: true,
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        this.resourceEligibilityservice.deleteAllocation(id).subscribe({
          next: (res: any) => {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: res.message || 'Record deleted successfully.',
              showConfirmButton: true
            });
            this.getProjectAllocationlist();
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
    
}
