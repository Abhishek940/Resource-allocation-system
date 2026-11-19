import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
@Injectable({
  providedIn: 'root'
})
export class RoleService {

private apiUrl = 'http://127.0.0.1:8000/api/roles';
private headers = { 'Content-Type': 'application/json' };
httpOptions = {
    headers: new HttpHeaders({
      'Content-Type': 'application/json',
    })
  }
  constructor(private http: HttpClient) { }

   getroles(page: number = 1, perPage: number = 10): Observable<any> {
      return this.http.get(`${this.apiUrl}?page=${page}&per_page=${perPage}`);
   }

   getAllRoles(): Observable<any> {
      return this.http.get(`${this.apiUrl}?per_page=100`);
   }
  
   createRole(data: any): Observable<any> {
      return this.http.post(`${this.apiUrl}/create/role`, data, this.httpOptions);
   }

   getRoleById(id: number): Observable<any> {
      return this.http.get(`${this.apiUrl}/role/${id}`);
   }

   updateRole(id: number, data: any): Observable<any> {
      return this.http.post(`${this.apiUrl}/update/role/${id}`, data, this.httpOptions);
   }

   deleteRole(id: number): Observable<any> {
      return this.http.delete(`${this.apiUrl}/delete/role/${id}`);
   }
}
