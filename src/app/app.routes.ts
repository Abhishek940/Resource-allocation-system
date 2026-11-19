import { Routes } from '@angular/router';

import { LayoutComponent } from './layout/layout.component';
import { LoginComponent } from './login/login.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { EmployeeComponent } from './employee/employee.component';
import { ProjectComponent } from './project/project.component';
import { ResourceEligibilityComponent } from './resource-eligibility/resource-eligibility.component';
import { RoleComponent } from './role/role.component';
import { RoleViewComponent } from './role-view/role-view.component';
import { EmployeeViewComponent } from './employee-view/employee-view.component';
import { ProjectViewComponent } from './project-view/project-view.component';
import { authGuard } from './guard/auth.guard';
import {EmployeeProjectAllocationComponent} from './employee-project-allocation/employee-project-allocation.component'
import {SkillMatchingComponent} from './skill-matching/skill-matching.component';
import { SkillGapComponent } from './skill-gap/skill-gap.component';
export const routes: Routes = [
  {
    path: 'login',
    component: LoginComponent
  },
 {
  path: '',
  component: LayoutComponent,
  canActivate: [authGuard],
  children: [
    { path: '', redirectTo: 'login', pathMatch: 'full' },

    {
      path: 'dashboard',
      component: DashboardComponent,
      data: {
        menu: {
          label: 'Dashboard',
          icon: 'bi-speedometer2',
          roles: [
            'System Administrator',
            'L&K Administrator',
            'Resource Manager / Reporting Authority',
            'Project Manager / Requirement Owner',
            'Management / Authorized Allocator',
            'Read-Only Management User'
          ]
        }
      }
    },

    {
      path: 'role-view',
      component: RoleViewComponent,
      data: {
        menu: {
          label: 'Role Management',
          icon: 'bi-person-badge',
          roles: ['System Administrator']
        }
      }
    },

    {
      path: 'employee-view',
      component: EmployeeViewComponent,
      data: {
        menu: {
          label: 'Employee Management',
          icon: 'bi-people',
          roles: [
            'System Administrator',
            'Resource Manager',
            'L&K Administrator'
          ]
        }
      }
    },

      {
      path: 'add-employee',
      component: EmployeeComponent,
      data: {
        menu: {
          label: 'Employee Management',
          icon: 'bi-people',
          roles: [
            'System Administrator',
           // 'Resource Manager',
            //'L&K Administrator'
          ],
          hidden:true
        }
      }
    },

    {
      path: 'projects',
      component: ProjectViewComponent,
      data: {
        menu: {
          label: 'Project Management',
          icon: 'bi-book',
          roles: [
            'System Administrator',
            'Project Manager'
          ]
        }
      }
    },

     {
      path: 'create-projects',
      component: ProjectComponent,
      data: {
        menu: {
          label: 'Project Management',
          icon: 'bi-book',
          roles: [
            'System Administrator',
            'Project Manager'
          ],
          hidden: true
        }
      }
    },

    {
      path: 'projects-allocation',
      component: EmployeeProjectAllocationComponent,
      data: {
        menu: {
          label: 'Project Allocation',
          icon: 'bi-book',
          roles: [
            'System Administrator',
            'Management / Authorized Allocator'
          ]
        }
      }
    },

    {
      path: 'resource-eligibility',
      component: ResourceEligibilityComponent,
      data: {
        menu: {
          label: 'Resource Eligibility',
          icon: 'bi-app',
          roles: [
            'System Administrator',
            'Project Manager',
            'L&K Administrator',
            'Resource Manager',
            'Management',
          ]
        }
      }
    },

    {
      path: 'skill-match',
      component: SkillMatchingComponent,
      data: {
        menu: {
          label: 'AI Skill Matching',
          icon: 'bi-stars',
          roles: [
            'System Administrator',
            'Project Manager',
            'Management',
            'Read-Only Management User'
          ]
        }
      }
    },

    {
      path: 'skill-gap',
      component: SkillGapComponent,
      data: {
        menu: {
          label: 'Skill Gap Analysis',
          icon: 'bi-stars',
          roles: [
            'System Administrator',
            'Project Manager',
            'Management',
          'L&K Administrator',
          ]
        }
      }
    }

  ]
},


  
  { path: '**', redirectTo: 'login' } 
];