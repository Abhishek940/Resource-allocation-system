import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, FormsModule } from '@angular/forms';
import Swal from 'sweetalert2';
import { RoleService } from '../services/role.service';
import { EmployeeService } from '../services/employee.service';

@Component({
  selector: 'app-role-view',
  imports: [CommonModule, RouterLink, FormsModule, ReactiveFormsModule],
  templateUrl: './role-view.component.html',
  styleUrl: './role-view.component.css'
})
export class RoleViewComponent implements OnInit {

  roleList: any[] = [];
  paginatedRoleList: any[] = [];
  employeelist: any[] = [];
  isLoading: boolean = false;

  // Pagination Properties
  currentPage: number = 1;
  pageSize: number = 5;
  totalPages: number = 1;
  totalRecords: number = 0;
  pageSizes: number[] = [5, 10, 25, 50];

  // Role Options for Dropdown
  roleOptions: string[] = [
    'System Administration',
    'Project Manager',
    'Resource Manager',
    'Technical Lead',
    'Software Engineer',
    'Senior Developer',
    'QA Engineer',
    'DevOps Engineer',
    'Solution Architect',
    'Business Analyst',
    'Authorized Allocator',
    'Readonly Management'
  ];

  // Edit Role Modal
  isEditModalOpen: boolean = false;
  editRoleForm!: FormGroup;
  selectedRoleId: number | null = null;

  constructor(
    private router: Router,
    private fb: FormBuilder,
    private roleService: RoleService,
    private employeeService: EmployeeService
  ) { }

  ngOnInit(): void {
    this.editRoleForm = this.fb.group({
      emp_id: ['', [Validators.required]],
      roleName: ['', [Validators.required]],
      status: ['', [Validators.required]]
    });

    this.getEmployeelist();
    this.getRoles();
  }

  goBack(): void {
    this.router.navigate(['/dashboard']);
  }

  getEmployeelist(): void {
    this.employeeService.getDropdownEmployees().subscribe({
      next: (res: any) => {
        this.employeelist = res.data || [];
      },
      error: (error: any) => {
        console.error('API Error:', error);
      }
    });
  }

  getRoles(): void {
    this.isLoading = true;
    this.roleService.getroles().subscribe({
      next: (res: any) => {
        let data = [];
        if (Array.isArray(res.data)) {
          data = res.data;
        } else if (res.data && Array.isArray(res.data.data)) {
          data = res.data.data;
        } else if (Array.isArray(res)) {
          data = res;
        }
        this.roleList = data;
        // Collect existing unique role names into dropdown list
        this.roleList.forEach((r: any) => {
          if (r.roleName && !this.roleOptions.includes(r.roleName)) {
            this.roleOptions.push(r.roleName);
          }
        });
        this.updatePagination();
        this.isLoading = false;
      },
      error: (error: any) => {
        console.error('API Error:', error);
        this.isLoading = false;
      }
    });
  }

  // Pagination Logic
  updatePagination(): void {
    this.totalRecords = this.roleList.length;
    this.totalPages = Math.ceil(this.totalRecords / this.pageSize) || 1;

    if (this.currentPage > this.totalPages) {
      this.currentPage = this.totalPages;
    }
    if (this.currentPage < 1) {
      this.currentPage = 1;
    }

    const startIndex = (this.currentPage - 1) * this.pageSize;
    const endIndex = startIndex + Number(this.pageSize);
    this.paginatedRoleList = this.roleList.slice(startIndex, endIndex);
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

  getEmployeeName(empId: any): string {
    const emp = this.employeelist.find((e: any) => String(e.id) === String(empId));
    return emp ? emp.employeeName : `Employee #${empId}`;
  }

  //  Edit Modal
  openEditModal(role: any): void {
    this.selectedRoleId = role.id;
    if (role.roleName && !this.roleOptions.includes(role.roleName)) {
      this.roleOptions.push(role.roleName);
    }
    this.editRoleForm.patchValue({
      emp_id: role.emp_id,
      roleName: role.roleName,
      status: String(role.status)
    });
    this.isEditModalOpen = true;
  }

  // Close Edit Modal
  closeEditModal(): void {
    this.isEditModalOpen = false;
    this.selectedRoleId = null;
    this.editRoleForm.reset();
  }

  // Save / Update Role
  updateRole(): void {
    if (this.editRoleForm.invalid) {
      Swal.fire({
        icon: 'warning',
        title: 'Required',
        text: 'All fields are required.'
      });
      return;
    }

    if (!this.selectedRoleId) return;

    this.roleService.updateRole(this.selectedRoleId, this.editRoleForm.value).subscribe({
      next: (res: any) => {
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: res.message || 'Role updated successfully.',
          confirmButtonText: 'ok',
          showConfirmButton: true
        });
        this.closeEditModal();
        this.getRoles();
      },
      error: (error: any) => {
        console.error('Error!:', error);
        Swal.fire({
          icon: 'error',
          title: 'Update Failed',
          text: error.error?.message || 'Something went wrong while updating the role.',
          confirmButtonText:'Ok',
          showConfirmButton:true
        });
      }
    });
  }

  // Delete
  deleteRole(id: number): void {
    Swal.fire({
      title: 'Are you sure?',
      text: 'Do you really want to delete this role?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        this.roleService.deleteRole(id).subscribe({
          next: (res: any) => {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: res.message || 'Role deleted successfully.',
              confirmButtonText:'Ok',
              showConfirmButton: true
            });
            this.getRoles();
          },
          error: (error: any) => {
            console.error('Delete Error:', error);
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error.error?.message || 'Failed to delete role.'
            });
          }
        });
      }
    });
  }
}
