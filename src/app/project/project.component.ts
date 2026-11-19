import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { FormBuilder, FormGroup, Validators ,ReactiveFormsModule} from '@angular/forms';
import Swal from 'sweetalert2';
import { ProjectService } from '../services/project.service';


@Component({
  selector: 'app-project',
  imports: [CommonModule, FormsModule, RouterLink, ReactiveFormsModule],
  templateUrl: './project.component.html',
  styleUrl: './project.component.css'
})
export class ProjectComponent {
  projectForm!: FormGroup;
  constructor(private router: Router,
    private fb: FormBuilder,
    private projectService: ProjectService
  ) { }

   ngOnInit(): void {
  
    this.projectForm = this.fb.group({
      projectName: ['', [Validators.required]],
      project_des: ['', [Validators.required]],
      businessDomain: ['', [Validators.required]],
      requirementOwner: ['', [Validators.required]],
      requirementPriority: ['', [Validators.required]],
      location: ['', [Validators.required]],
      duration: ['', [Validators.required]],
      requiredTechnology: ['', [Validators.required]],
      mandatorySkills: ['', [Validators.required]],
      preferredSkills: ['', [Validators.required]],
      minProficiency: ['', [Validators.required]],
      minExperience: ['', [Validators.required]],
      numberOfResources: ['', [Validators.required]],
      startFrom: ['', [Validators.required]],
    //  availabilityDuration: ['', [Validators.required]],
      requiredAvailability: ['', [Validators.required]],
      projectDuration:['',[Validators.required]],
      status:[0,[Validators.required]]
    });
  
  }
  
  isEditing = true;
  feedbackMessage = '';
  feedbackType: 'success' | 'info' | 'warning' = 'success';

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
    'Bhubaneshwar',
    'Delhi',
    'Patna, India',
    'Client Onsite (International)',
    'Remote (Any Location)'
  ];

  workModes = [
    'Patna',
    'Bhubaneshwar',
    'Delhi',
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

  
  goBack(): void {
    this.router.navigate(['/dashboard']);
  }

  // Save as Draft
/*   saveAsDraft(): void {
    this.project.status = 'Draft';
    this.showMessage(`Requirement saved as Draft (${this.project.version}). You can continue editing.`, 'info');
  } */

  //  Edit Requirement 
/*   enableEdit(): void {
    this.isEditing = true;
    // Bump version minor, e.g. v1.0 -> v1.1
    const currentVerParts = this.project.version.replace('v', '').split('.');
    const major = parseInt(currentVerParts[0], 10) || 1;
    const minor = (parseInt(currentVerParts[1], 10) || 0) + 1;
    this.project.version = `v${major}.${minor}`;
    this.showMessage(`Editing mode enabled. Requirement version bumped to ${this.project.version}.`, 'info');
  } */


 

   saveProject(): void {
       if (this.projectForm.get('projectName')?.invalid) {
        Swal.fire({
          icon: 'warning',
          title: 'Required',
          text: 'Project Name is required.',
        });
        return;
      }
      this.projectService.createProject(this.projectForm.value).subscribe({
        next: (res) => {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: res.message,
            confirmButtonText:'ok',
            showConfirmButton: true
          });
  
          this.projectForm.reset();
          this.router.navigate(['/projects']);
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
   this.projectForm.reset({
    projectName: '',
    project_des: '',
    businessDomain: '',
    requirementOwner: '',
    requirementPriority: '',
    location: '',
    duration: '',
    requiredTechnology: '',
    mandatorySkills: '',
    preferredSkills: '',
    minProficiency: '',
    minExperience: '',
    numberOfResources: '',
    joiningDate: '',
    availabilityDuration: '',
    requiredAvailability: '',
    projectDuration: '',
    status:'',
  });
  }

  
  
 
}
