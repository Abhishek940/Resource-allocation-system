import { Component, EventEmitter, Output, OnInit } from '@angular/core';
import { Router, NavigationEnd } from '@angular/router';
import { filter } from 'rxjs';

@Component({
  selector: 'app-header',
  imports: [],
  templateUrl: './header.component.html',
  styleUrl: './header.component.css'
})
export class HeaderComponent implements OnInit {
  @Output() toggleSidebar = new EventEmitter<void>();
  pageTitle = 'Dashboard';
  username: string = '';
  role: string = '';
  constructor(private router: Router) {
    this.updateTitle(this.router.url);
    this.router.events
      .pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd))
      .subscribe((event) => {
        this.updateTitle(event.urlAfterRedirects || event.url);
      });
  }

   ngOnInit(){

   this.username = localStorage.getItem('username') || '';
   this. role = localStorage.getItem('role') || '';
   //console.log('Username:', this.username);
  // console.log('Role:', this.role);
   
  }
  private updateTitle(url: string): void {
    if (url.includes('/employee')) {
      this.pageTitle = 'Employee Management';
    } else if (url.includes('/project')) {
      this.pageTitle = 'Project Management';
    } else {
      this.pageTitle = 'Dashboard';
    }
  }


  onToggle(): void {
    this.toggleSidebar.emit();
  }

  logOut():void {
    localStorage.removeItem('token');
    localStorage.removeItem('username');
    localStorage.removeItem('role');
    localStorage.removeItem('refreshToken');
    this.router.navigate(['/login']);
  }
}


