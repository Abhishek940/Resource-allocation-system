import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, throwError } from 'rxjs';
@Injectable({
  providedIn: 'root'
})
export class EmployeeService {
  private loginUrl = 'http://127.0.0.1:8000/api';
  private apiUrl = 'http://127.0.0.1:8000/api/employees';


  private headers = { 'Content-Type': 'application/json' };
  

  httpOptions = {
    headers: new HttpHeaders({
      'Content-Type': 'application/json',
    })
  }
  constructor(private http: HttpClient) { }

  
  getEmployees(page: number = 1, perPage: number = 10): Observable<any> {
    return this.http.get(`${this.apiUrl}?page=${page}&per_page=${perPage}`);
  }

  getAllEmployees(): Observable<any> {
    return this.http.get(`${this.apiUrl}?per_page=100`);
  }

  createEmployee(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/create/employee`, data, this.httpOptions);
  }

  getEmployeeById(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/employee/${id}`);
  }

  updateEmployee(id: number, data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/update/employee/${id}`, data, this.httpOptions);
  }

  deleteEmployee(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/delete/employee/${id}`);
  }

  getDropdownEmployees(): Observable<any> {
    return this.http.get(`${this.apiUrl}/employeeslist`);
  }

  login(data:any):Observable<any>{
    return this.http.post(`${this.loginUrl}/login`,data,this.httpOptions);
  }


}
