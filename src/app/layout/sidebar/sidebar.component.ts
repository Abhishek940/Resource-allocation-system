
import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  RouterLink,
  RouterLinkActive,
  Router,
  Routes
} from '@angular/router';

interface MenuItem {
  label: string;
  icon: string;
  route: string;
}

@Component({
  selector: 'app-sidebar',
  imports: [CommonModule, RouterLink, RouterLinkActive],
  templateUrl: './sidebar.component.html',
  styleUrl: './sidebar.component.css'
})
export class SidebarComponent {

  menuItems: MenuItem[] = [];

  constructor(private router: Router) {
    this.menuItems = this.getMenuItems(this.router.config);
  }

  private getMenuItems(
    routes: Routes,
    parentPath = ''
  ): MenuItem[] {

    const userRole = (localStorage.getItem('role') || '').trim().toLowerCase();
    console.log('Current Login Role:', userRole);
    return routes.flatMap(route => {
      const currentPath = [parentPath, route.path]
        .filter(Boolean)
        .join('/');
      const menu = route.data?.['menu'];
      const isHidden = menu?.['hidden'] === true;
      const allowedRoles: string[] = menu?.['roles'] || [];
      const hasAccess = allowedRoles.some(
        role => role.trim().toLowerCase() === userRole
      );

      const currentItem: MenuItem[] =
        menu && hasAccess && !isHidden
          ? [{
              label: menu['label'],
              icon: menu['icon'],
              route: '/' + currentPath
            }]
          : [];

      const childItems = route.children
        ? this.getMenuItems(route.children, currentPath)
        : [];

      return [...currentItem, ...childItems];

    });
  }
}
