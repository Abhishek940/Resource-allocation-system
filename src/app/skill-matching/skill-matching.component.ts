import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ResourceEligibilityService } from '../services/resource-eligibility.service';
import { formatDate } from '@angular/common';
type CandidateView = 'eligible' | 'all' | 'excluded';

@Component({
  selector: 'app-skill-matching',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './skill-matching.component.html',
  styleUrl: './skill-matching.component.css'
})
export class SkillMatchingComponent implements OnInit {
  result: any = null;
  response: any = null;
  errorMessage = '';
  loading = false;

  searchText = '';
  selectedView: CandidateView = 'eligible';
  selectedCandidate: any = null;

  projects: any[] = [];
  candidates: any[] = [];
  excludedCandidates: any[] = [];
  projectId: number | null = null;
  projectCode: any[] = [];
  aiStatus: any;
  aiDeepDiveLoading = false;
  // Generative AI State
  activeTab: 'ranking' | 'ai-squad' = 'ranking';
  aiTeamDeployment: any = null;
  loadingAiSquad = false;
  aiDeepDiveCandidate: any = null;
  selectedCandidateAiInsights: any = null;
  loadingCandidateAi = false;
  checkingAiStatus = false;

  constructor(private eligibilityService: ResourceEligibilityService) { }

  ngOnInit(): void {
    this.loadProjects();
    this.checkAiStatus();
  }

  checkAiStatus(): void {
    this.checkingAiStatus = true;
    this.eligibilityService.getAiStatus().subscribe({
      next: (res: any) => {
        this.aiStatus = res;
        this.checkingAiStatus = false;
      },
      error: (err: any) => {
        this.aiStatus = err?.error ?? { success: false, message: 'Could not connect to AI endpoint.' };
        this.checkingAiStatus = false;
      }
    });
  }

  loadProjects(): void {
    this.loading = true;
    this.errorMessage = '';

    this.eligibilityService.getActiveProjects().subscribe({
      next: (res: any) => {
        this.projects = res?.data ?? [];
        this.loading = false;
      },
      error: (error: any) => {
        console.error('Error loading projects:', error);
        this.errorMessage = 'Unable to load projects.';
        this.loading = false;
      }
    });
  }

  onProjectChange(event: Event): void {
    const value = (event.target as HTMLSelectElement).value;
    this.projectId = value ? Number(value) : null;

    this.result = null;
    this.response = null;
    this.candidates = [];
    this.excludedCandidates = [];
    this.selectedCandidate = null;
    this.searchText = '';
    this.selectedView = 'eligible';
    this.aiTeamDeployment = null;
    this.selectedCandidateAiInsights = null;
    this.aiDeepDiveCandidate = null;
    this.activeTab = 'ranking';

    if (this.projectId !== null) {
      this.loadMatches();
    }
  }

  loadMatches(): void {
    if (this.projectId === null) {
      this.result = null;
      this.response = null;
      this.candidates = [];
      this.excludedCandidates = [];
      return;
    }

    this.loading = true;
    this.errorMessage = '';

    this.eligibilityService.getResourceMatches(this.projectId).subscribe({
      next: (res: any) => {
        this.response = res;
        this.result = res?.data ?? res;
        console.log('result', this.result);
        this.candidates = Array.isArray(this.result?.data)
          ? this.result.data
          : [];

        this.excludedCandidates = Array.isArray(this.result?.excluded)
          ? this.result.excluded
          : [];

        this.aiTeamDeployment = this.result?.aiTeamDeployment ?? null;

        this.selectedView = 'eligible';
        this.searchText = '';
        this.loading = false;
      },
      error: (error: any) => {
        console.error('Error loading matches:', error);
        this.errorMessage =
          error?.error?.message ?? 'Failed to load matching results.';
        this.loading = false;
      }
    });
  }

  // API data contains ranked matches; excluded records are separate.
  get eligibleCandidates(): any[] {
    return (this.candidates || []).filter(
      (candidate: any) => candidate.deploymentEligible === true
    );
  }

  get eligibleCount(): number {
    return this.eligibleCandidates.length;
  }

  get excludedCount(): number {
    return this.nonDeployableCandidates.length +
      this.excludedCandidates.length;
  }

  get allCount(): number {
    return this.candidates.length + this.excludedCandidates.length;
  }

  get filteredCandidates(): any[] {
    const search = this.searchText.trim().toLowerCase();

    let source: any[] = [];

    switch (this.selectedView) {
      case 'eligible':
        source = this.eligibleCandidates;
        break;

      case 'excluded':
        source = [
          ...this.nonDeployableCandidates,
          ...this.excludedCandidates
        ];
        break;

      case 'all':
      default:
        source = [...this.candidates, ...this.excludedCandidates];
        break;
    }

    return source.filter((candidate: any) => {
      const name = this.getEmployeeName(candidate).toLowerCase();
      const code = this.getEmployeeCode(candidate).toLowerCase();

      return !search ||
        name.includes(search) ||
        code.includes(search);
    });
  }

  getCandidateStatus(candidate: any): string {
    const employee = this.getEmployee(candidate);

    if (candidate?.deploymentEligible === true) {
      return 'Eligible';
    }

    if (candidate?.hasConflict === true) {
      return 'Allocation Conflict';
    }

    const missingSkills =
      candidate?.skillAnalysis?.missingMandatorySkills ?? [];

    const futureAvailability =
      candidate?.availabilityStatus === 'Future Availability' ||
      employee?.availableAtProjectStart === false;

    if (missingSkills.length && futureAvailability) {
      return 'Skill Gap + Future Availability';
    }

    if (missingSkills.length) {
      return 'Partial Skill Match';
    }

    if (futureAvailability) {
      return 'Future Availability';
    }

    return 'Not Immediately Deployable';
  }

  getAvailabilityMessage(candidate: any): string {
    const employee = this.getEmployee(candidate);

    if (candidate?.hasConflict === true) {
      const conflicts = employee?.conflictingAllocations ?? [];

      if (conflicts.length) {
        return conflicts.map((item: any) =>
          `${item.projectCode} - ${item.projectName}: ` +
          `${item.allocationPercentage}% allocated ` +
          `(${item.startDate} to ${item.endDate})`
        ).join('; ');
      }

      return 'Existing project allocation conflict.';
    }

    if (
      candidate?.availabilityStatus === 'Future Availability' ||
      employee?.availableAtProjectStart === false
    ) {
      return `Available from ${employee?.availableFrom ?? 'date not provided'}.`;
    }

    if (candidate?.deploymentEligible === true) {
      return `Available from ${employee?.availableFrom ?? 'project start date'}.`;
    }

    return 'Availability not confirmed.';
  }

  get nonDeployableCandidates(): any[] {
    return (this.candidates || []).filter(
      candidate => candidate?.deploymentEligible !== true
    );
  }

  showCandidates(view: CandidateView): void {
    this.selectedView = view;
    this.searchText = '';
    this.selectedCandidate = null;
  }

  getEmployee(candidate: any): any {
    return candidate?.employee ?? candidate ?? {};
  }

  getEmployeeName(candidate: any): string {
    return String(this.getEmployee(candidate)?.employeeName ?? 'Unknown Employee');
  }

  getEmployeeCode(candidate: any): string {
    const employee = this.getEmployee(candidate);
    return String(employee?.employeeCode ?? employee?.employeeId ?? '');
  }

  getRank(candidate: any, index: number): number {
    return Number(candidate?.rank) || index + 1;
  }

  getScoreBreakdown(candidate: any): any {
    return candidate?.scores ?? candidate?.scoreBreakdown ?? {};
  }

  getSkillCoverage(candidate: any): number {
    const employee = this.getEmployee(candidate);
    return Number(
      employee?.mandatoryMatchPercentage ??
      candidate?.skillAnalysis?.mandatoryMatchPercentage ??
      this.getScoreBreakdown(candidate)?.skillScore ??
      0
    );
  }

  getProficiencyScore(candidate: any): number {
    return Number(this.getScoreBreakdown(candidate)?.proficiencyScore ?? 0);
  }

  getExperienceScore(candidate: any): number {
    return Number(this.getScoreBreakdown(candidate)?.experienceScore ?? 0);
  }

  getEvidenceScore(candidate: any): number {
    const scores = this.getScoreBreakdown(candidate);
    return Number(
      scores?.evidenceScore ??
      scores?.assessmentScore ??
      this.getEmployee(candidate)?.assessmentScore ??
      0
    );
  }

  getAvailabilityScore(candidate: any): number {
    return Number(this.getScoreBreakdown(candidate)?.availabilityScore ?? 0);
  }

  getSuitabilityScore(candidate: any): number {
    return Number(
      candidate?.suitabilityScore ??
      this.getEmployee(candidate)?.suitabilityScore ??
      0
    );
  }

  /*  isCandidateEligible(candidate: any): boolean {
     if (this.excludedCandidates.includes(candidate)) {
       return false;
     }
 
     if (candidate?.mandatoryConstraintsMet !== undefined) {
       return candidate.mandatoryConstraintsMet === true;
     }
 
     if (candidate?.employee?.skillEligible !== undefined) {
       return candidate.employee.skillEligible === true;
     }
 
     return true;
   }
  */

  isCandidateEligible(candidate: any): boolean {
    return candidate?.deploymentEligible === true;
  }

  getScoreClass(score: number): string {
    return score >= 80 ? 'score-strong'
      : score >= 65 ? 'score-good'
        : score >= 50 ? 'score-medium'
          : 'score-low';
  }

  getCategory(candidate: any): string {
    if (!this.isCandidateEligible(candidate)) {
      return this.getCandidateStatus(candidate);
    }

    const score = this.getSuitabilityScore(candidate);

    return score >= 80 ? 'Strong Match'
      : score >= 65 ? 'Potential Match'
        : score >= 50 ? 'Development Candidate'
          : 'Low Match';
  }

  getSkillAnalysis(candidate: any): any {
    return candidate?.skillAnalysis ?? {};
  }

  getSkillList(skills: any): string[] {
    if (Array.isArray(skills)) {
      return skills.map((skill: any) => String(skill));
    }

    return typeof skills === 'string'
      ? skills.split(',').map(skill => skill.trim()).filter(Boolean)
      : [];
  }

  getExplanationText(candidate: any): string {
    const employee = this.getEmployee(candidate);
    const reasons = candidate?.reasons ?? employee?.reasons ?? [];

    return candidate?.explanation
      ?? candidate?.recommendationExplanation
      ?? employee?.recommendationExplanation
      ?? (Array.isArray(reasons) && reasons.length
        ? reasons.join(' ')
        : 'No explanation was provided by the API.');
  }

  getRecommendation(candidate: any): string {
    return candidate?.recommendation
      ?? (this.isCandidateEligible(candidate)
        ? 'Recommended for stakeholder review'
        : 'Excluded from eligible matches');
  }

  hasCertification(candidate: any): boolean {
    const scores = this.getScoreBreakdown(candidate);
    return Boolean(
      scores?.hasCertification ??
      this.getEmployee(candidate)?.certifications
    );
  }

  hasProjectEvidence(candidate: any): boolean {
    const scores = this.getScoreBreakdown(candidate);
    return Boolean(
      scores?.hasProjectEvidence ??
      this.getEmployee(candidate)?.projectExperience
    );
  }

  openDetails(candidate: any): void {
    this.selectedCandidate = candidate;
  }

  closeDetails(): void {
    this.selectedCandidate = null;
  }

  trackByRank(index: number, candidate: any): number | string {
    return candidate?.employee?.employeeId
      ?? candidate?.employeeId
      ?? candidate?.rank
      ?? index;
  }


  get averageScore(): number {
    const eligible = this.eligibleCandidates;

    if (!eligible.length) {
      return 0;
    }

    const total = eligible.reduce(
      (sum: number, candidate: any) =>
        sum + this.getSuitabilityScore(candidate),
      0
    );

    return Math.round((total / eligible.length) * 100) / 100;
  }

  // --- Generative AI Methods & Helpers ---

  setActiveTab(tab: 'ranking' | 'ai-squad'): void {
    this.activeTab = tab;
    if (tab === 'ai-squad' && !this.aiTeamDeployment && this.projectId) {
      this.loadAiTeamSquad();
    }
  }

  loadAiTeamSquad(): void {
    if (!this.projectId) return;
    this.loadingAiSquad = true;
    this.eligibilityService.getAiDeploymentRecommendation(this.projectId).subscribe({
      next: (res: any) => {
        this.aiTeamDeployment = res?.data?.aiTeamDeployment ?? res?.aiTeamDeployment ?? res?.data;
        this.loadingAiSquad = false;
      },
      error: (err: any) => {
        console.error('Error loading AI deployment recommendation:', err);
        this.loadingAiSquad = false;
      }
    });
  }

  loadCandidateAiDeepDive(candidate: any): void {

    const employee = this.getEmployee(candidate);

    const employeeId =
      employee?.employeeId ??
      employee?.id ??
      candidate?.employeeId;

    if (!employeeId || !this.projectId) {
      return;
    }

    this.aiDeepDiveCandidate = candidate;
    this.selectedCandidateAiInsights = null;
    this.loadingCandidateAi = true;

    const requestedProjectId = this.projectId;
    const requestedEmployeeId = employeeId;

    this.eligibilityService
      .getCandidateAiInsights(requestedProjectId, requestedEmployeeId)
      .subscribe({

        next: (res: any) => {

          // Ignore an old response if the user changed candidate/project.
          if (
            this.projectId !== requestedProjectId ||
            this.aiDeepDiveCandidate !== candidate
          ) {
            return;
          }

          this.selectedCandidateAiInsights =
            res?.data?.aiInsights ??
            res?.aiInsights ??
            res?.data ??
            null;

          this.loadingCandidateAi = false;

          console.log(
            'Candidate AI Deep-Dive:',
            this.selectedCandidateAiInsights
          );
        },

        error: (err: any) => {

          console.error('Error fetching deep-dive AI insights:', err);

          this.loadingCandidateAi = false;
          this.selectedCandidateAiInsights = null;
        }
      });
  }

  closeAiDeepDive(): void {
    this.aiDeepDiveCandidate = null;
    this.selectedCandidateAiInsights = null;
  }


  getRecommendationSource(): string {
    const candidates = [
      ...(this.candidates ?? []),
      ...(this.excludedCandidates ?? [])
    ];

    const sources = candidates
      .map(candidate => this.getAiDeployment(candidate)?.source)
      .filter(source =>
        source === 'generative_ai' || source === 'heuristic'
      );

    const uniqueSources = [...new Set(sources)];

    if (uniqueSources.length === 0) {
      return 'unavailable';
    }

    if (uniqueSources.length > 1) {
      return 'mixed';
    }

    return uniqueSources[0];
  }

  getAiDeployment(candidate: any): any {

    // Use Deep-Dive insights only for the candidate being evaluated.
    if (
      this.aiDeepDiveCandidate === candidate &&
      this.selectedCandidateAiInsights
    ) {
      const deepDive = this.selectedCandidateAiInsights;

      return deepDive?.aiDeployment
        ?? deepDive?.aiInsights
        ?? deepDive;
    }

    return candidate?.aiDeployment
      ?? candidate?.employee?.aiDeployment
      ?? null;
  }



  /*  getRecommendedRole(candidate: any): string {
     return this.getAiDeployment(candidate)?.recommendedRole ??
       (this.isCandidateEligible(candidate) ? 'Core Implementation Specialist' : 'Candidate on Hold');
   } */


  getRecommendedRole(candidate: any): string {
    if (!this.isCandidateEligible(candidate)) {
      return 'Candidate on Hold';
    }

    const ai = this.getAiDeployment(candidate);

    if (!ai || ai.available !== true) {
      return 'Recommendation Unavailable';
    }

    const isGroq =
      ai.source === 'generative_ai' &&
      ai.provider === 'groq' &&
      ai.fallbackUsed === false;

    const isFallback =
      ai.source === 'heuristic' &&
      ai.fallbackUsed === true;

    if (!isGroq && !isFallback) {
      return 'Recommendation Unavailable';
    }

    const role = (ai.recommendedRole || 'Role Not Specified').trim();

    const employee = candidate?.employee ?? {};
    const skills = employee.employeeSkills;

    const technologies = Array.isArray(skills)
      ? [...new Set(
        skills
          .map((skill: any) =>
            typeof skill === 'string'
              ? skill.trim()
              : (skill?.skillName || skill?.name || '').trim()
          )
          .filter(Boolean)
      )].join(', ')
      : (employee.primarySkill || '').trim();

    // Remove existing technologies in parentheses from the API role.
    const cleanRole = role.replace(/\s*\([^()]*\)\s*$/, '').trim();

    // Append technologies only once.
    return technologies
      ? `${cleanRole} (${technologies})`
      : role;
  }



  getSquadMemberRole(member: any): string {
    const role = (member?.assignedRole || 'Full Stack Developer').trim();

    // Remove existing technologies in parentheses, if present.
    const cleanRole = role.replace(/\s*\([^()]*\)\s*$/, '').trim();

    // Use the squad member's skills returned by the API.
    const skills = Array.isArray(member?.keyContributions)
      ? member.keyContributions
        .filter((skill: any) => typeof skill === 'string' && skill.trim())
      : [];

    // Remove duplicate technologies, ignoring case.
    const uniqueSkills = [
      ...new Map(
        skills.map((skill: string) => [
          skill.trim().toLowerCase(),
          skill.trim()
        ])
      ).values()
    ];

    return uniqueSkills.length
      ? `${cleanRole} (${uniqueSkills.join(', ')})`
      : role;
  }


  getDeploymentReadiness(candidate: any): string {

    const employee = this.getEmployee(candidate);

    if (candidate?.deploymentEligible === true) {
      return 'Immediate Deployment Ready';
    }

    if (candidate?.hasConflict === true) {
      return 'Allocation Conflict - Hold';
    }

    const missingSkills =
      candidate?.skillAnalysis?.missingMandatorySkills ?? [];

    const futureAvailability =
      candidate?.availabilityStatus === 'Future Availability' ||
      employee?.availableAtProjectStart === false;

    if (missingSkills.length > 0 && futureAvailability) {
      return 'Skill Gap + Future Availability - Hold';
    }

    if (missingSkills.length > 0) {
      return 'Skill Gap - Hold';
    }

    if (futureAvailability) {
      return 'Future Availability - Hold';
    }

    return 'Deployment Contingency / Hold';
  }

  getAiRecommendedReadiness(candidate: any): string {
    return this.getDeploymentReadiness(candidate);
  }

  /* getCandidateExclusionReasons(candidate: any): string[] {
    const reasons: string[] = [];
    const employee = this.getEmployee(candidate);
  
    if (candidate?.hasConflict === true) {
      const conflicts = employee?.conflictingAllocations ?? [];
  
      if (conflicts.length > 0) {
        conflicts.forEach((allocation: any) => {
          reasons.push(
            `Existing allocation conflict with ${allocation.projectCode} - ${allocation.projectName} (${allocation.allocationPercentage}% allocation).`
          );
        });
      } else {
        reasons.push('Candidate has an existing allocation conflict.');
      }
    }
  
    if (
      employee?.availableAtProjectStart === false ||
      candidate?.availabilityStatus === 'Future Availability'
    ) {
      reasons.push(
        `Candidate is unavailable on the project kickoff date (${employee?.projectStartDate ?? 'Not specified'}) and becomes available from ${employee?.availableFrom ?? 'a future date'}.`
      );
    }
  
    const missingSkills =
      candidate?.skillAnalysis?.missingMandatorySkills ?? [];
  
    if (missingSkills.length > 0) {
      reasons.push(
        `Missing mandatory skills: ${missingSkills.join(', ')}.`
      );
    }
  
    if (employee?.experienceEligible === false) {
      reasons.push(
        `Experience requirement not met. Required: ${employee?.requiredExperience ?? 'Not specified'} years; available: ${employee?.employeeExperience ?? 'Not specified'} years.`
      );
    }
  
    if (employee?.proficiencyEligible === false) {
      reasons.push('Required proficiency level not achieved.');
    }
  
    return reasons;
  } */

  getReadinessBadgeClass(readiness: string): string {
    if (!readiness) return 'readiness-neutral';
    const lower = readiness.toLowerCase();
    if (lower.includes('immediate')) return 'readiness-immediate';
    if (lower.includes('ramp-up') || lower.includes('rampup')) return 'readiness-rampup';
    if (lower.includes('mentorship')) return 'readiness-mentorship';
    return 'readiness-hold';
  }

  getReadinessIcon(readiness: string): string {
    if (!readiness) return 'bi-info-circle';
    const lower = readiness.toLowerCase();
    if (lower.includes('immediate')) return 'bi-lightning-charge-fill';
    if (lower.includes('ramp-up') || lower.includes('rampup')) return 'bi-graph-up-arrow';
    if (lower.includes('mentorship')) return 'bi-people-fill';
    return 'bi-shield-exclamation';
  }

  getRampUpTime(candidate: any): string {

    const ai = this.getAiDeployment(candidate);

    return ai?.estimatedRampUpTime
      ?? 'Not specified';
  }

  getKeyStrengths(candidate: any): string[] {
    const strengths = this.getAiDeployment(candidate)?.keyStrengths;

    return Array.isArray(strengths) ? strengths : [];
  }
  getAiSkillGaps(candidate: any): any[] {
    const gaps = this.getAiDeployment(candidate)?.skillGaps;
    return Array.isArray(gaps) ? gaps : [];
  }

  getAiDeploymentRisks(candidate: any): any[] {
    const risks = this.getAiDeployment(candidate)?.deploymentRisks;
    return Array.isArray(risks) ? risks : [];
  }

  getAiSemanticCoverage(candidate: any): number {
    return Number(this.getAiDeployment(candidate)?.semanticCoverageScore ?? this.getSkillCoverage(candidate));
  }

  getAiExecutiveSummary(candidate: any): string {

    const name = this.getEmployeeName(candidate);

    if (candidate?.deploymentEligible !== true) {

      if (candidate?.hasConflict === true) {

        const conflicts =
          this.getAllocationConflictDetails(candidate);

        const conflictText = conflicts.length
          ? conflicts.map(item =>
            `${item.projectCode} - ${item.projectName} ` +
            `(${item.allocationPercentage}% allocation, ` +
            `until ${item.endDate ?? 'an unspecified date'})`
          ).join('; ')
          : 'an existing project allocation';

        return `${name} has a strong technical suitability profile, but cannot be deployed to this project because of ${conflictText}. Deployment can be reconsidered after the existing allocation is resolved.`;
      }

      // Keep your existing future availability and skill-gap logic here.
    }

    const ai = this.getAiDeployment(candidate);

    return ai?.aiRecommendation
      ?? ai?.executiveSummary
      ?? candidate?.explanation
      ?? 'The candidate meets the current deployment conditions.';
  }


  /*   getAllocationConflictDetails(candidate: any): any[] {
    const employee = this.getEmployee(candidate);
  
    const conflicts = employee?.conflictingAllocations ?? [];
  
    return conflicts.map((allocation: any) => {
      const endDate = allocation?.endDate;
  
      return {
        projectCode: allocation?.projectCode ?? 'Unknown Project',
        projectName: allocation?.projectName ?? 'Unknown Project',
        allocationPercentage: Number(
          allocation?.allocationPercentage ?? 0
        ),
        startDate: allocation?.startDate ?? null,
        endDate: endDate,
        availableAfter: endDate
          ? this.addOneDay(endDate)
          : 'Not specified'
      };
    });
  } */

  /* addOneDay(date: string): string {
    const parsedDate = new Date(`${date}T00:00:00`);
  
    if (isNaN(parsedDate.getTime())) {
      return 'Not specified';
    }
  
    parsedDate.setDate(parsedDate.getDate() + 1);
  
    const year = parsedDate.getFullYear();
    const month = String(parsedDate.getMonth() + 1).padStart(2, '0');
    const day = String(parsedDate.getDate()).padStart(2, '0');
  
    return `${year}-${month}-${day}`;
  } */

  getAllocationConflictDetails(candidate: any): any[] {

    const employee = this.getEmployee(candidate);

    const allocations =
      candidate?.conflictingAllocations ??
      employee?.conflictingAllocations ??
      [];

    if (!Array.isArray(allocations)) {
      return [];
    }

    // Normalize allocation data
    const normalized = allocations.map((item: any) => {

      const project = item?.project ?? {};

      return {
        projectId:
          item?.projectId ??
          item?.project_id ??
          project?.projectId ??
          project?.id,

        projectCode:
          item?.projectCode ??
          item?.project_code ??
          project?.projectCode ??
          project?.project_code ??
          'Unknown Project',

        projectName:
          item?.projectName ??
          item?.project_name ??
          project?.projectName ??
          project?.project_name ??
          project?.name ??
          'Unknown Project',

        allocationPercentage:
          Number(
            item?.allocationPercentage ??
            item?.allocation_percentage ??
            item?.allocatedPercentage ??
            item?.percentage ??
            0
          ),

        startDate:
          item?.startDate ??
          item?.start_date ??
          item?.allocationStart ??
          item?.allocation_start ??
          null,

        endDate:
          item?.endDate ??
          item?.end_date ??
          item?.allocationEnd ??
          item?.allocation_end ??
          null
      };
    });

    // Remove duplicate project allocation records
    const uniqueAllocations = normalized.filter(
      (item, index, self) => {

        const key = [
          item.projectId ?? item.projectCode,
          item.startDate ?? '',
          item.endDate ?? '',
          item.allocationPercentage
        ].join('_');

        return index === self.findIndex(other => {

          const otherKey = [
            other.projectId ?? other.projectCode,
            other.startDate ?? '',
            other.endDate ?? '',
            other.allocationPercentage
          ].join('_');

          return key === otherKey;
        });
      }
    );

    return uniqueAllocations.map(item => ({
      ...item,

      availableAfter: item.endDate
        ? this.addOneDay(item.endDate)
        : null
    }));
  }

  addOneDay(date: string): string {
    const parsedDate = new Date(`${date}T00:00:00`);

    if (isNaN(parsedDate.getTime())) {
      return 'Not specified';
    }

    parsedDate.setDate(parsedDate.getDate() + 1);

    const year = parsedDate.getFullYear();
    const month = String(parsedDate.getMonth() + 1).padStart(2, '0');
    const day = String(parsedDate.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }

  getCandidateExclusionReasons(candidate: any): string[] {
    const employee = this.getEmployee(candidate);
    const reasons: string[] = [];

    // Allocation conflicts
    if (candidate?.hasConflict === true) {
      const conflicts = this.getAllocationConflictDetails(candidate);

      if (conflicts.length) {
        conflicts.forEach(allocation => {
          reasons.push(
            `Allocated to ${allocation.projectCode} - ${allocation.projectName} ` +
            `(${allocation.allocationPercentage}%) until ${allocation.endDate ?? 'an unspecified date'}.`
          );
        });
      } else {
        reasons.push('Existing project allocation conflict.');
      }
    }

    // Future availability
    if (candidate?.availabilityStatus === 'Future Availability' ||
      employee?.availableAtProjectStart === false
    ) {
      reasons.push(
        `Project starts on ${employee?.projectStartDate
          ? formatDate(employee.projectStartDate, 'dd-MMM-yyyy', 'en-US')
          : 'the planned kickoff date'
        }, ` +
        `but this candidate becomes available from ${employee?.availableFrom
          ? formatDate(employee.availableFrom, 'dd-MMM-yyyy', 'en-US')
          : 'an unspecified date'
        }.`
      );
    }

    // Missing mandatory skills
    const missingSkills =
      candidate?.skillAnalysis?.missingMandatorySkills ?? [];

    if (missingSkills.length) {
      reasons.push(
        `Missing mandatory skills: ${missingSkills.join(', ')}.`
      );
    }

    // Experience
    if (employee?.experienceEligible === false) {
      reasons.push(
        `Experience requirement not met. Required: ${employee?.requiredExperience} years; ` +
        `available: ${employee?.employeeExperience} years.`
      );
    }

    // Proficiency
    if (employee?.proficiencyEligible === false) {
      reasons.push('Required proficiency level not achieved.');
    }

    return reasons;
  }


}