import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
@Injectable({
  providedIn: 'root'
})
export class ResourceEligibilityService {

  private apiUrl = 'http://127.0.0.1:8002/api';

  constructor(private http:HttpClient) { }

    // get project details
    getProjectById(projectId:number){
      return this.http.get<any>(`${this.apiUrl}/projects/${projectId}`);
    }

     getActiveProjects(): Observable<any> {
      return this.http.get<any>(`${this.apiUrl}/projects`);
    }

        // get employee allocation for a date range
      getConfirmedAllocations(
          employeeId: number,
          startDate: string,
          endDate: string
        ): Observable<any> {

          const params = new HttpParams()
            .set('startDate', startDate)
            .set('endDate', endDate);
          return this.http.get<any>(`${this.apiUrl}/employees/${employeeId}/allocations`,{ params });
        }

    // Get complete project eligibility results
      checkProjectEligibility(projectId: number): Observable<any> {
        return this.http.get<any>(
          `${this.apiUrl}/projects/${projectId}/eligibility`
        );
      }


   // Create an allocation 
  insertAllocation(data: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/allocations`, data);
  }

  getAllocationlist():Observable<any>{
    return this.http.get<any>(`${this.apiUrl}/allocationslist`)
  }

  deleteAllocation(id:number):Observable<any>{
    return this.http.delete<any>(`${this.apiUrl}/delete/allocation/${id}`)
  }

  
   getResourceMatches(projectId: number): Observable<any> {
    return this.http.get<any>(
      `${this.apiUrl}/projects/${projectId}/resource-matches`
    );
  }

  // Generative AI Project Squad Deployment Recommendation
  getAiDeploymentRecommendation(projectId: number): Observable<any> {
    return this.http.get<any>(
      `${this.apiUrl}/projects/${projectId}/ai-deployment-recommendation`
    );
  }

  // Generative AI Deep-Dive Insights for an individual candidate
  getCandidateAiInsights(projectId: number, employeeId: number): Observable<any> {
    return this.http.get<any>(
      `${this.apiUrl}/projects/${projectId}/candidate/${employeeId}/ai-insights`
    );
  }

  // AI Semantic Skill Matching
  matchSkillsWithAi(requiredSkills: string[], candidateSkills: string[]): Observable<any> {
    return this.http.post<any>(
      `${this.apiUrl}/ai/match-skills`,
      { requiredSkills, candidateSkills }
    );
  }

  // Check live Gemini / AI Provider Status
  getAiStatus(): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/ai/status`);
  }
  

 skillGapAnalysis(projectId: number, employeeId: number) {
  return this.http.get<any>(
    `${this.apiUrl}/skill-gap-analysis/${projectId}/${employeeId}`
  );
}

}
