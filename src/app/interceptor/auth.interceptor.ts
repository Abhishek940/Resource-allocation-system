
import { HttpInterceptorFn } from '@angular/common/http';
import { Router } from '@angular/router';
import { inject } from '@angular/core';
import { jwtDecode } from 'jwt-decode';

export const authInterceptor: HttpInterceptorFn = (req, next) => {

  const router = inject(Router);
  const token = localStorage.getItem('token');

  // No token: redirect to login
  if (!token) {
    if (!router.url.includes('/login')) {
      router.navigate(['/login']);
    }

    return next(req);
  }

  try {

    // Decode JWT token
    const decoded: any = jwtDecode(token);

    // Current time in seconds
    const currentTime = Math.floor(Date.now() / 1000);

    // Check token expiration
    if (!decoded.exp || decoded.exp <= currentTime) {

      localStorage.clear();

      router.navigate(['/login']);

      return next(req);
    }

  } catch (error) {

    // Invalid JWT token
    localStorage.clear();

    router.navigate(['/login']);

    return next(req);
  }

  // Attach token to API request
  const authReq = req.clone({
    setHeaders: {
      Authorization: `Bearer ${token}`
    }
  });

  return next(authReq);
};
