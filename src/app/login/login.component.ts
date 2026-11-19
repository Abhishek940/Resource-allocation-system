import { Component } from '@angular/core';
import { Router } from '@angular/router';
import Swal from 'sweetalert2';
import { CommonModule } from '@angular/common';
import { EmployeeService } from '../services/employee.service';
import { FormBuilder, FormGroup, Validators ,ReactiveFormsModule} from '@angular/forms';
@Component({
  selector: 'app-login',
   imports: [CommonModule,ReactiveFormsModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent {
loginForm!: FormGroup;

constructor(
    private fb: FormBuilder,
    private router: Router,
    private employeeService: EmployeeService,
  ) {}

  ngOnInit(){

    this.loginForm = this.fb.group({
      username:['', [Validators.required]],
      password:['',[Validators.required]]
    });
  }

    onSubmit(): void {
         
         if (this.loginForm.invalid) {
            if (this.loginForm.get('username')?.invalid) {
              Swal.fire({
                icon: 'error',
                title: 'Error...',
                text: 'Username is required!',
                confirmButtonText: 'OK'
              });
              return;
            }
            if (this.loginForm.get('password')?.invalid) {
              Swal.fire({
                icon: 'error',
                title: 'Error...',
                text: 'Password is required!',
                confirmButtonText: 'OK'
              });
              return;
            }
           } else {
              const credential: { username: any; password: any; } = {
              username: this.loginForm.get('username')?.value,
              password: this.loginForm.get('password')?.value,
             
            };
                
            this.employeeService.login(credential).subscribe({
              next:(res:any) => {
                const token = res?.data?.access_token;
                const username = res?.data?.user?.username;
                const role = res?.data?.user?.roleName;

                localStorage.setItem('token', token);
                localStorage.setItem('refreshToken', res.refreshToken); // Refresh token
                localStorage.setItem('username', username);
                localStorage.setItem('role', role);
                          
                Swal.fire({
                  icon: 'success',
                  title: 'Success!',
                  text:res.msg,
                  confirmButtonText: 'OK'
                })
                .then(() => {
                this.router.navigate(['/dashboard']);
               }); 
              },
             error: (error) => {
                console.error('Error:', error);
                let errorMessage = error?.error?.msg || 'An unexpected error occurred';
                Swal.fire({
                  icon: 'error',
                  title: 'Error!',
                  text: errorMessage,
                  confirmButtonText: 'OK'
                });
              }
           });
          }
        }
}
