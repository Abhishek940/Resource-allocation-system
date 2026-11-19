import { Component, Inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { FormBuilder, FormGroup, Validators ,ReactiveFormsModule} from '@angular/forms';
import Swal from 'sweetalert2';
import {RoleService} from '../services/role.service';
import { EmployeeService } from '../services/employee.service';
@Component({
  selector: 'app-role',
  imports: [CommonModule, FormsModule, ReactiveFormsModule, RouterLink],
  templateUrl: './role.component.html',
  styleUrl: './role.component.css'
})
export class RoleComponent {

roleForm!: FormGroup;
employeelist:any[]=[];
  constructor(
    private router: Router,
    private fb: FormBuilder,
    private roleService: RoleService,
    private employeeService: EmployeeService
  ) { }

  ngOnInit(): void {
  
    this.roleForm = this.fb.group({
      emp_id:['', [Validators.required]],
      roleName: ['', [Validators.required]],
      status:['',[Validators.required]]
    });
  
    this.getEmployeelist();
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

  departments = [
    'Engineering & Product',
    'Data Science & AI',
    'Cloud & DevOps',
    'Quality Assurance (QA)',
    'Cybersecurity',
    'Enterprise Solutions',
    'Consulting & Delivery'
  ];

   

  goBack(): void {
    this.router.navigate(['/dashboard']);
  }

  saveRole(): void {
  
     if (this.roleForm.get('roleName')?.invalid) {
      Swal.fire({
        icon: 'warning',
        title: 'Required',
        text: 'Role Name is required.',
      });
      return;
    }
   this.roleService.createRole(this.roleForm.value).subscribe({
  next: (res: any) => {
    Swal.fire({
      icon: 'success',
      title: 'Success!',
      text: res.message,
      showConfirmButton: true,
      confirmButtonText: 'OK',
      // showCancelButton: true,
      // cancelButtonText: 'Cancel'
    }).then((result) => {
      // This runs when the user clicks a button
      if (result.isConfirmed) {
        this.roleForm.reset();
        // this.router.navigate(['/employees']);
      }
    });
  },

      error: (error:any) => {
        console.error('API Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Submission Failed',
          text: error.error?.message || 'Something went wrong while saving the employee.',
        });
      }
    });
  
  }

  resetForm(): void {
   this.roleForm.reset({
    roleName: '',
    status: ''
  });
   

  }
}

