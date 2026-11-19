import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { FormBuilder, FormGroup, Validators ,ReactiveFormsModule} from '@angular/forms';
import Swal from 'sweetalert2';
import { EmployeeService } from '../services/employee.service';
@Component({
  selector: 'app-employee',
  imports: [CommonModule, FormsModule, RouterLink, ReactiveFormsModule],
  templateUrl: './employee.component.html',
  styleUrl: './employee.component.css'
})
export class EmployeeComponent {
employeeForm!: FormGroup;
  constructor(
    private router: Router,
    private fb: FormBuilder,
    private employeeService: EmployeeService
  ) { }

  ngOnInit(): void {
  
    this.employeeForm = this.fb.group({
      employeeName: ['', [Validators.required]],
      designation: ['', [Validators.required]],
      department: ['', [Validators.required]],
      location: ['', [Validators.required]],
      primaryskill: ['', [Validators.required]],
      proficiency: ['', [Validators.required]],
      experience: ['', [Validators.required]],
      secondarySkills: ['', [Validators.required]],
      tertiarySkills: ['', [Validators.required]],
      assessmentScore: ['', [Validators.required]],
      validationStatus: ['', [Validators.required]],
      certifications: ['', [Validators.required]],
      resourceStatus: ['', [Validators.required]],
      availableFrom: ['', [Validators.required]],
      projectExperience: ['', [Validators.required]]
    });
  
  }
  

  employee = {
    employeeCode: '',
    employeeName: '',
    designation: '',
    department: '',
    location: '',
    primarySkill: '',
    proficiency: '',
    experience: '',
    secondarySkills: '',
    tertiarySkills: '',
    assessmentScore: '',
    validationStatus: '',
    certifications: '',
    resourceStatus: '',
    availableFrom: '',
    projectExperience: ''
  };

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

  successMessage = false;

  goBack(): void {
    this.router.navigate(['/dashboard']);
  }

  saveEmployee(): void {
     if (this.employeeForm.get('employeeName')?.invalid) {
      Swal.fire({
        icon: 'warning',
        title: 'Required',
        text: 'Employee Name is required.',
      });
      return;
    }
    this.employeeService.createEmployee(this.employeeForm.value).subscribe({
      next: (res) => {
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: res.message,
          confirmButtonText:'ok',
          showConfirmButton: true
        });

        this.employeeForm.reset();
        this.router.navigate(['/employee-view']);
      },
      error: (error) => {
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
   this.employeeForm.reset({
    employeeName: '',
    designation: '',
    department: '',
    location: '',
    primarySkill: '',
    proficiency: '',
    experience: '',
    secondarySkills: '',
    tertiarySkills: '',
    validationStatus: '',
    assessmentScore: '',
    certifications: '',
    resourceStatus: '',
    availableFrom: '',
    projectExperience: ''
  });
  }
}
