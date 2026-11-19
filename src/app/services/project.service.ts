import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, throwError } from 'rxjs';
@Injectable({
  providedIn: 'root'
})
export class ProjectService {

  private loginUrl = 'http://127.0.0.1:8000/api';
  private apiUrl = 'http://127.0.0.1:8001/api';


  private headers = { 'Content-Type': 'application/json' };


  httpOptions = {
    headers: new HttpHeaders({
      'Content-Type': 'application/json',
    })
  }
  constructor(private http: HttpClient) { }


  getProjects(page: number = 1, perPage: number = 10): Observable<any> {
    return this.http.get(`${this.apiUrl}/projects?page=${page}&per_page=${perPage}`);
  }


  createProject(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/create/project`, data, this.httpOptions);
  }

  getProjectById(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/project/${id}`);
  }

  updateProject(id: number, data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/update/project/${id}`, data, this.httpOptions);
  }

  deleteProject(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/delete/project/${id}`);
  }

  getProjectdropdownlist(): Observable<any> {
    return this.http.get(`${this.apiUrl}/projectdropdown`);
  }

  createAllocation(payload: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/employee-project-allocations`, payload);
  }

  getAllocations(): Observable<any> {
    return this.http.get(`${this.apiUrl}/employee-project-allocations`);
  }

  // dashboard route
  getDashboardCounts(): Observable<any> {
    return this.http.get(`${this.apiUrl}/dashboard`);
  }

  getMatchingResources(): Observable<any> {
    return this.http.get(`${this.apiUrl}/dashboard/matching-resources`);
  }

  getstrongMatchCandidate():Observable<any>{
    return this.http.get(`${this.apiUrl}/dashboard/strong-match-candidates`);
  }

  getUpskillingCandidates():Observable<any>{
    return this.http.get(`${this.apiUrl}/dashboard/upskilling-candidates`)
  }

}
