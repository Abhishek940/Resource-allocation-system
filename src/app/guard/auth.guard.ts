import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';

export const authGuard: CanActivateFn = (route, state) => {
  const router = inject(Router);
  const token = localStorage.getItem('token');

  // Check if token exists
  if (token) {
    return true; // Allow access
  }

  // If not logged in, redirect to login page
  router.navigate(['/login']);
  return false;
};