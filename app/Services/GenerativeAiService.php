<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * GenerativeAiService
 *
 * Provider: OpenAI-compatible endpoint (Groq)
 * Model:    openai/gpt-oss-20b
 * Endpoint: https://api.groq.com/openai/v1/chat/completions
 */
class GenerativeAiService
{
    protected array $config;

    // =========================================================================
    // Skill Ontology — transferability weights for semantic skill matching
    // =========================================================================
    protected array $skillOntology = [
        // Frontend
        'react'      => ['category' => 'Frontend', 'family' => 'spa_ui',    'related' => ['next.js' => 0.90, 'typescript' => 0.85, 'javascript' => 0.85, 'vue' => 0.80, 'angular' => 0.75, 'redux' => 0.90, 'tailwind' => 0.70], 'concepts' => 'Component architecture, Virtual DOM, reactive state management, hooks lifecycle'],
        'angular'    => ['category' => 'Frontend', 'family' => 'spa_ui',    'related' => ['typescript' => 0.95, 'rxjs' => 0.90, 'react' => 0.75, 'vue' => 0.75, 'javascript' => 0.80, 'ngrx' => 0.85],                          'concepts' => 'Two-way binding, dependency injection, RxJS reactive streams, modular SPA architecture'],
        'vue'        => ['category' => 'Frontend', 'family' => 'spa_ui',    'related' => ['javascript' => 0.85, 'typescript' => 0.80, 'react' => 0.80, 'nuxt.js' => 0.90, 'angular' => 0.75],                                    'concepts' => 'Reactivity system, single-file components, Vuex/Pinia state management'],
        'typescript' => ['category' => 'Language', 'family' => 'typed_js',  'related' => ['javascript' => 0.95, 'angular' => 0.85, 'react' => 0.80, 'node.js' => 0.80],                                                          'concepts' => 'Static typing, interfaces, generics, compile-time contract safety'],
        'javascript' => ['category' => 'Language', 'family' => 'typed_js',  'related' => ['typescript' => 0.85, 'react' => 0.80, 'node.js' => 0.80, 'angular' => 0.75, 'vue' => 0.80],                                          'concepts' => 'ES6+, async/await event loop, DOM manipulation, functional patterns'],

        // Backend PHP
        'php'        => ['category' => 'Language', 'family' => 'php_eco',   'related' => ['laravel' => 0.90, 'symfony' => 0.85, 'codeigniter' => 0.80, 'mysql' => 0.75, 'rest api' => 0.75],                                    'concepts' => 'Server-side processing, OOP, PSR standards, composer dependency management'],
        'laravel'    => ['category' => 'Backend',  'family' => 'php_eco',   'related' => ['php' => 0.95, 'symfony' => 0.80, 'eloquent' => 0.95, 'mysql' => 0.80, 'rest api' => 0.85, 'livewire' => 0.85],                       'concepts' => 'MVC architecture, Eloquent ORM, service container, RESTful routing, queue workers'],
        'symfony'    => ['category' => 'Backend',  'family' => 'php_eco',   'related' => ['php' => 0.95, 'laravel' => 0.85, 'doctrine' => 0.90, 'rest api' => 0.85],                                                             'concepts' => 'Enterprise PHP components, HTTP kernel, bundle architecture, dependency injection'],

        // Backend Java
        'java'        => ['category' => 'Language', 'family' => 'jvm_eco',   'related' => ['spring boot' => 0.90, 'hibernate' => 0.85, 'kotlin' => 0.80, 'microservices' => 0.80],                                               'concepts' => 'Strong typing, JVM memory management, multi-threading, enterprise design patterns'],
        'spring boot' => ['category' => 'Backend',  'family' => 'jvm_eco',   'related' => ['java' => 0.95, 'hibernate' => 0.85, 'microservices' => 0.85, 'spring cloud' => 0.85, 'rest api' => 0.85],                           'concepts' => 'IoC container, dependency injection, JPA/Hibernate, auto-configuration, microservice architecture'],

        // Backend Python
        'python'  => ['category' => 'Language', 'family' => 'python_eco', 'related' => ['django' => 0.85, 'fastapi' => 0.85, 'flask' => 0.80, 'machine learning' => 0.80, 'pandas' => 0.80],                                    'concepts' => 'Data structures, scripting, clean syntax, scientific computing, REST APIs'],
        'django'  => ['category' => 'Backend',  'family' => 'python_eco', 'related' => ['python' => 0.95, 'flask' => 0.80, 'fastapi' => 0.80, 'postgresql' => 0.75, 'rest api' => 0.85],                                        'concepts' => 'Batteries-included MVC framework, Django ORM, authentication, admin panel'],
        'fastapi' => ['category' => 'Backend',  'family' => 'python_eco', 'related' => ['python' => 0.95, 'pydantic' => 0.90, 'django' => 0.75, 'docker' => 0.75, 'rest api' => 0.90],                                          'concepts' => 'Async ASGI high-performance API development, Pydantic data validation, OpenAPI specs'],

        // Backend Node.js
        'node.js' => ['category' => 'Backend', 'family' => 'node_eco', 'related' => ['javascript' => 0.90, 'typescript' => 0.85, 'express' => 0.90, 'nestjs' => 0.85, 'rest api' => 0.85],                                       'concepts' => 'Asynchronous non-blocking I/O, event-driven runtime, NPM ecosystem, microservices'],
        'express' => ['category' => 'Backend', 'family' => 'node_eco', 'related' => ['node.js' => 0.95, 'javascript' => 0.85, 'rest api' => 0.90, 'nestjs' => 0.80],                                                             'concepts' => 'Minimalist HTTP routing, middleware pipeline, REST endpoint design'],
        'nestjs'  => ['category' => 'Backend', 'family' => 'node_eco', 'related' => ['typescript' => 0.95, 'node.js' => 0.90, 'angular' => 0.75, 'spring boot' => 0.70, 'rest api' => 0.90],                                    'concepts' => 'Enterprise modular architecture, TypeScript decorators, dependency injection'],

        // Databases
        'mysql'      => ['category' => 'Database', 'family' => 'relational_db', 'related' => ['postgresql' => 0.85, 'sql' => 0.95, 'mariadb' => 0.95, 'database design' => 0.90], 'concepts' => 'Relational schema design, indexing, foreign keys, ACID transactions, query tuning'],
        'postgresql' => ['category' => 'Database', 'family' => 'relational_db', 'related' => ['mysql' => 0.85, 'sql' => 0.95, 'jsonb' => 0.80, 'database design' => 0.90],         'concepts' => 'Advanced relational queries, JSONB document storage, procedural SQL, high concurrency'],
        'mongodb'    => ['category' => 'Database', 'family' => 'nosql_db',      'related' => ['nosql' => 0.95, 'redis' => 0.70, 'node.js' => 0.75, 'express' => 0.70],             'concepts' => 'Document-oriented schema, aggregation pipelines, sharding, high-throughput caching'],
        'redis'      => ['category' => 'Database', 'family' => 'inmemory_cache','related' => ['caching' => 0.95, 'pub/sub' => 0.85, 'memcached' => 0.85],                           'concepts' => 'In-memory key-value caching, Pub/Sub messaging, session store, rate limiting'],

        // DevOps & Cloud
        'docker'     => ['category' => 'DevOps', 'family' => 'containers',    'related' => ['kubernetes' => 0.85, 'ci/cd' => 0.80, 'aws' => 0.75, 'linux' => 0.80],                  'concepts' => 'Containerization, Dockerfile multi-stage builds, compose orchestration, microservice packaging'],
        'kubernetes' => ['category' => 'DevOps', 'family' => 'containers',    'related' => ['docker' => 0.90, 'helm' => 0.85, 'cloud' => 0.80, 'aws' => 0.75],                       'concepts' => 'Container orchestration, pod autoscaling, ingress control, deployment rollouts, service mesh'],
        'aws'        => ['category' => 'Cloud',  'family' => 'cloud_infra',   'related' => ['azure' => 0.80, 'gcp' => 0.80, 'docker' => 0.75, 'devops' => 0.80],                     'concepts' => 'Cloud architecture, EC2/S3/RDS/Lambda, IAM security policies, high availability'],
        'ci/cd'      => ['category' => 'DevOps', 'family' => 'automation',    'related' => ['github actions' => 0.90, 'jenkins' => 0.85, 'docker' => 0.80, 'gitlab ci' => 0.90],     'concepts' => 'Continuous integration, automated testing, delivery pipelines, deployment automation'],

        // API & Architecture
        'rest api'      => ['category' => 'Architecture', 'family' => 'api_comm', 'related' => ['graphql' => 0.75, 'api development' => 0.95, 'microservices' => 0.80, 'json' => 0.90], 'concepts' => 'HTTP verbs, status codes, stateless authentication, OpenAPI documentation, resource design'],
        'graphql'       => ['category' => 'Architecture', 'family' => 'api_comm', 'related' => ['rest api' => 0.75, 'node.js' => 0.80, 'react' => 0.75, 'apollo' => 0.85],             'concepts' => 'Schema definition language, query resolvers, avoiding over/under-fetching, mutations'],
        'microservices' => ['category' => 'Architecture', 'family' => 'sys_arch', 'related' => ['docker' => 0.80, 'rest api' => 0.85, 'event-driven' => 0.80, 'kafka' => 0.75],        'concepts' => 'Distributed systems, service boundaries, circuit breakers, event buses, resilience'],
    ];

    public function __construct()
    {
        $this->config = config('ai', []);
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Generate AI recommendation for an individual candidate.
     * Calls Groq API first; falls back to built-in heuristic engine on failure.
     */
  public function generateCandidateRecommendation(
    array $project,
    array $candidate,
    array $evidence = [],
    bool $forceRefresh = false
): array {

    Log::info('STEP 1: generateCandidateRecommendation called', [
        'project_id' => $project['id'] ?? $project['projectId'] ?? null,
        'force_refresh' => $forceRefresh
    ]);

    // Get Groq API key
    $apiKey = $this->config['openai']['api_key']
        ?? env('OPENAI_API_KEY');

    if (empty($apiKey)) {

        Log::error('Groq API key is missing.');

        return $this->buildPhpCandidateFallback(
            $project,
            $candidate,
            $evidence,
            'missing_api_key'
        );
    }

    try {

        Log::info('STEP 2: Calling Groq candidate API');

        // Call Groq directly. No cache.
        $result = $this->callGroqForCandidate(
            $project,
            $candidate,
            $evidence
        );

        Log::info('STEP 3: Groq candidate response validation', [
            'response_type' => gettype($result),
            'response_keys' => is_array($result)
                ? array_keys($result)
                : [],
            'has_aiRecommendation' => is_array($result)
                && isset($result['aiRecommendation'])
        ]);

        // Validate Groq response
        if (
            is_array($result)
            && isset($result['aiRecommendation'])
            && is_string($result['aiRecommendation'])
            && trim($result['aiRecommendation']) !== ''
        ) {

            $result['available'] = true;
            $result['source'] = 'generative_ai';
            $result['provider'] = 'groq';
            $result['modelUsed'] = $result['modelUsed']
                ?? 'Groq';
            $result['fallbackUsed'] = false;
            $result['generatedAt'] = now()->toIso8601String();

            Log::info('STEP 4: Groq recommendation generated successfully.');

            return $result;
        }

        Log::warning('Groq returned an invalid or empty recommendation.');

        return $this->buildPhpCandidateFallback(
            $project,
            $candidate,
            $evidence,
            'invalid_or_unavailable_response'
        );

    } catch (\Throwable $e) {

        Log::error('Groq candidate recommendation exception', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        return $this->buildPhpCandidateFallback(
            $project,
            $candidate,
            $evidence,
            'provider_error'
        );
    }
}

private function buildPhpCandidateFallback(
    array $project,
    array $candidate,
    array $evidence,
    string $reason
): array {
    $result = $this->buildHeuristicCandidateRecommendation(
        $project,
        $candidate,
        $evidence
    );

    $result['available'] = true;
    $result['source'] = 'heuristic';
    $result['provider'] = null;
    $result['modelUsed'] = 'Built-in PHP Heuristic Engine';
    $result['fallbackUsed'] = true;
    $result['fallbackReason'] = $reason;

    return $result;
}
    /**
     * Generate AI team squad recommendation for N required resources.
     * Calls Groq API first; falls back to built-in heuristic engine on failure.
     */
      /*   public function generateProjectTeamRecommendation(
            array $project,
            array $rankedCandidates,
            bool $forceRefresh = false
        ): array {
            $projectId = $project['id']
                ?? $project['projectId']
                ?? 0;

            $cacheKey = "ai_team_squad_proj_{$projectId}_count_"
                . count($rankedCandidates);

            if ($forceRefresh) {
                Cache::forget($cacheKey);
            }

            return Cache::remember(
                $cacheKey,
                $this->config['cache_ttl'] ?? 3600,
                function () use ($project, $rankedCandidates) {
                    try {
                        $result = $this->callGroqForTeam(
                            $project,
                            $rankedCandidates
                        );

                        if (
                            is_array($result)
                            && !empty($result['executiveSummary'])
                        ) {
                            $result['available'] = true;
                            $result['source'] = 'generative_ai';
                            $result['provider'] = 'groq';
                            $result['fallbackUsed'] = false;

                            return $result;
                        }
                    } catch (\Throwable $e) {
                        Log::warning(
                            'Groq team recommendation failed.',
                            ['error' => $e->getMessage()]
                        );
                    }

                    $result = $this->buildHeuristicTeamRecommendation(
                        $project,
                        $rankedCandidates
                    );

                    $result['available'] = true;
                    $result['source'] = 'heuristic';
                    $result['provider'] = null;
                    $result['modelUsed'] = 'Built-in PHP Heuristic Engine';
                    $result['fallbackUsed'] = true;
                    $result['fallbackReason'] = 'provider_error_or_invalid_response';

                    return $result;
                }
            );
        } */

      /*       public function generateProjectTeamRecommendation(
                array $project,
                array $rankedCandidates,
                bool $forceRefresh = false
            ): array {
                // Always generate a fresh recommendation.
                if (!empty($this->config['groq']['api_key'])) {
                    $result = $this->callGroqForTeam(
                        $project,
                        $rankedCandidates
                    );

                    if (!empty($result)) {
                        return $result;
                    }
                }

    // Use heuristic recommendation if Groq fails.
            return $this->buildHeuristicTeamRecommendation(
                $project,
                $rankedCandidates
            );
        } */

            public function generateProjectTeamRecommendation(
    array $project,
    array $rankedCandidates,
    bool $forceRefresh = false
): array {

    try {
        // Always call Groq API. No cache.
        $result = $this->callGroqForTeam(
            $project,
            $rankedCandidates
        );

        if (
            is_array($result)
            && !empty($result['executiveSummary'])
        ) {
            $result['available'] = true;
            $result['source'] = 'generative_ai';
            $result['provider'] = 'groq';
            $result['fallbackUsed'] = false;

            return $result;
        }

    } catch (\Throwable $e) {

        Log::warning(
            'Groq team recommendation failed.',
            ['error' => $e->getMessage()]
        );
    }

    $result = $this->buildHeuristicTeamRecommendation(
        $project,
        $rankedCandidates
    );

    $result['available'] = true;
    $result['source'] = 'heuristic';
    $result['provider'] = null;
    $result['modelUsed'] = 'Built-in PHP Heuristic Engine';
    $result['fallbackUsed'] = true;
    $result['fallbackReason'] = 'provider_error_or_invalid_response';

    return $result;
}

    /**
     * Semantic skill matching between required and candidate skills.
     */
    public function matchSkillsSemantically(array $requiredSkills, array $candidateSkills): array
    {
        $normalizedReq  = array_map('strtolower', array_map('trim', $requiredSkills));
        $normalizedCand = array_map('strtolower', array_map('trim', $candidateSkills));

        $directMatches   = array_values(array_intersect($normalizedReq, $normalizedCand));
        $unmatchedReq    = array_values(array_diff($normalizedReq, $normalizedCand));
        $transferable    = [];

        foreach ($unmatchedReq as $reqSkill) {
            $bestMatch    = null;
            $bestScore    = 0.0;
            $bestConcepts = '';

            // Forward lookup in ontology
            if (isset($this->skillOntology[$reqSkill])) {
                $relatedMap = $this->skillOntology[$reqSkill]['related'] ?? [];
                $concepts   = $this->skillOntology[$reqSkill]['concepts'] ?? 'Shared domain architecture';

                foreach ($normalizedCand as $candSkill) {
                    if (isset($relatedMap[$candSkill]) && $relatedMap[$candSkill] > $bestScore) {
                        $bestScore    = $relatedMap[$candSkill];
                        $bestMatch    = $candSkill;
                        $bestConcepts = $concepts;
                    }
                }
            }

            // Reverse lookup if still weak
            if ($bestScore < 0.70) {
                foreach ($normalizedCand as $candSkill) {
                    if (isset($this->skillOntology[$candSkill]['related'][$reqSkill])) {
                        $score = $this->skillOntology[$candSkill]['related'][$reqSkill];
                        if ($score > $bestScore) {
                            $bestScore    = $score;
                            $bestMatch    = $candSkill;
                            $bestConcepts = $this->skillOntology[$candSkill]['concepts'] ?? 'Translatable architectural principles';
                        }
                    }
                }
            }

            if ($bestMatch && $bestScore >= 0.65) {
                $transferable[] = [
                    'requiredSkill'       => $reqSkill,
                    'candidateSkill'      => $bestMatch,
                    'similarityScore'     => round($bestScore * 100, 1),
                    'transferableConcepts'=> $bestConcepts,
                    'aiReasoning'         => "Candidate's proficiency in {$bestMatch} provides a high conceptual foundation for {$reqSkill} ({$bestConcepts}).",
                    'rampUpEstimate'      => $bestScore >= 0.85 ? '1-2 weeks (Low learning curve)' : '2-4 weeks (Moderate ramp-up)',
                ];
            }
        }

        $allCovered           = count($directMatches) + count($transferable);
        $totalRequired        = max(1, count($normalizedReq));
        $effectiveCoverage    = round(min(100, ($allCovered / $totalRequired) * 100), 2);

        return [
            'directMatches'       => $directMatches,
            'transferableMatches' => $transferable,
            'unmatchedSkills'     => array_values(array_diff($unmatchedReq, array_column($transferable, 'requiredSkill'))),
            'effectiveCoverageScore' => $effectiveCoverage,
        ];
    }

    // =========================================================================
    // GROQ / OPENAI-COMPATIBLE API CALLS
    // =========================================================================

    /**
     * Call Groq API for individual candidate recommendation.
     */
  protected function callGroqForCandidate(
    array $project,
    array $candidate,
    array $evidence
): ?array {

    $apiKey = $this->config['openai']['api_key']
        ?? env('OPENAI_API_KEY');

    $model = $this->config['openai']['model']
        ?? env('OPENAI_MODEL', 'openai/gpt-oss-20b');

    $endpoint = $this->config['openai']['endpoint']
        ?? env('AI_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions');

    $timeout = $this->config['openai']['timeout'] ?? 30;

    if (empty($apiKey)) {
        Log::error('Groq API key is missing.');
        return null;
    }

    try {

        $prompt = $this->buildCandidatePrompt(
            $project,
            $candidate,
            $evidence
        );

        $response = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->post($endpoint, [
                'model' => $model,
                'temperature' => 0.2,

                // Request JSON output
                'response_format' => [
                    'type' => 'json_object'
                ],

                'messages' => [
                    [
                        'role' => 'system',
                        'content' =>
                            'You are an enterprise AI resource allocation '
                            . 'and project deployment expert. '
                            . 'Return valid JSON only. '
                            . 'Do not include markdown code fences.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ]);

        // Log HTTP status
        Log::info('Groq candidate API response.', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'model' => $model
        ]);

        if (!$response->successful()) {

            Log::error('Groq API request failed.', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 2000)
            ]);

            return null;
        }

        // Get AI-generated content
        $content = $response->json('choices.0.message.content');

        Log::info('Groq candidate raw content.', [
            'content' => substr((string) $content, 0, 2000)
        ]);

        if (!is_string($content) || trim($content) === '') {

            Log::warning('Groq returned empty content.');

            return null;
        }

        // Parse JSON response
        $parsed = $this->parseJsonResponse($content);

        if (!is_array($parsed)) {

            Log::warning('Groq JSON parsing failed.', [
                'content' => substr($content, 0, 2000)
            ]);

            return null;
        }

        if (
            empty($parsed['aiRecommendation'])
            || !is_string($parsed['aiRecommendation'])
        ) {

            Log::warning('Groq response missing aiRecommendation.', [
                'parsed_response' => $parsed
            ]);

            return null;
        }

        $employee = $candidate['employee'] ?? $candidate;

        $skills = $this->extractSkillsList(
            $employee['employeeSkills']
                ?? $employee['primarySkill']
                ?? []
        );

        $groqRole = $parsed['recommendedRole']
            ?? $this->RecommendedRole($skills);

        $parsed['recommendedRole'] = $this->formatRecommendedRole(
            (string) $groqRole,
            $employee
        );

        $parsed['available'] = true;
        $parsed['source'] = 'generative_ai';
        $parsed['provider'] = 'groq';
        $parsed['modelUsed'] = "Groq ({$model})";
        $parsed['fallbackUsed'] = false;
        $parsed['generatedAt'] = now()->toIso8601String();

        Log::info('Groq candidate recommendation generated successfully.', [
            'employee_id' => $employee['employeeId'] ?? $employee['id'] ?? null,
            'model' => $model
        ]);

        return $parsed;

    } catch (\Throwable $e) {

        Log::error('Groq candidate generation exception.', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        return null;
    }
}

    /**
     * Call Groq API for team squad recommendation.
     */
  protected function callGroqForTeam(
    array $project,
    array $rankedCandidates
): ?array {
    $apiKey = $this->config['openai']['api_key']
        ?? env('OPENAI_API_KEY');

    $model = $this->config['openai']['model']
        ?? env('OPENAI_MODEL', 'openai/gpt-oss-20b');

    $endpoint = $this->config['openai']['endpoint']
        ?? env(
            'AI_ENDPOINT',
            'https://api.groq.com/openai/v1/chat/completions'
        );

    $timeout = $this->config['openai']['timeout'] ?? 30;

    if (empty($apiKey)) {
        return null;
    }

    $projJson = json_encode(
        $project,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );

    $topCandidates = array_slice($rankedCandidates, 0, 8);

    $candJson = json_encode(
        $topCandidates,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );

    $prompt = $this->buildTeamPrompt(
        $projJson ?: '{}',
        $candJson ?: '[]'
    );

    $response = Http::timeout($timeout)
        ->withToken($apiKey)
        ->post($endpoint, [
            'model' => $model,
            'temperature' => 0.2,
            'messages' => [
                [
                    'role' => 'system',
                    'content' =>
                        'You are an enterprise AI Project Staffing '
                        . 'and Squad Deployment Architect. '
                        . 'Always reply with valid JSON only. '
                        . 'Do not include markdown code fences.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

    if (!$response->successful()) {
        Log::warning('Groq team API error.', [
            'status' => $response->status(),
            'body' => substr($response->body(), 0, 500),
        ]);

        return null;
    }

    $content = $response->json('choices.0.message.content');
    $parsed = $this->parseJsonResponse($content);

    if (
        !is_array($parsed)
        || empty($parsed['executiveSummary'])
    ) {
        Log::warning(
            'Groq team response is missing executiveSummary.',
            ['raw' => substr((string) $content, 0, 500)]
        );

        return null;
    }

    $squad = $parsed['recommendedSquad'] ?? [];

    if (!is_array($squad)) {
        $squad = [];
    }

    foreach ($squad as $index => $member) {
        if (!is_array($member)) {
            continue;
        }

        $matchedEmployee = null;

        foreach ($topCandidates as $candidate) {
            $employee = $candidate['employee'] ?? $candidate;

            $candidateId = $employee['employeeId']
                ?? $employee['id']
                ?? null;

            $memberId = $member['employeeId']
                ?? $member['id']
                ?? null;

            $candidateCode = $employee['employeeCode'] ?? null;
            $memberCode = $member['employeeCode'] ?? null;

            $idMatches = $memberId !== null
                && $candidateId !== null
                && (string) $memberId === (string) $candidateId;

            $codeMatches = !empty($memberCode)
                && !empty($candidateCode)
                && strcasecmp(
                    (string) $memberCode,
                    (string) $candidateCode
                ) === 0;

            if ($idMatches || $codeMatches) {
                $matchedEmployee = $employee;
                break;
            }
        }

        if ($matchedEmployee === null) {
            // Do not attach technologies from the wrong employee.
            continue;
        }

        $employeeSkills = $this->extractSkillsList(
            $matchedEmployee['employeeSkills']
                ?? $matchedEmployee['primarySkill']
                ?? []
        );

        $groqRole = $member['assignedRole']
            ?? $this->RecommendedRole($employeeSkills);

        $member['assignedRole'] = $this->formatRecommendedRole(
            (string) $groqRole,
            $matchedEmployee
        );

        $member['employeeId'] = $matchedEmployee['employeeId']
            ?? $matchedEmployee['id']
            ?? $member['employeeId']
            ?? null;

        $member['employeeName'] = $matchedEmployee['employeeName']
            ?? $member['employeeName']
            ?? 'Candidate';

        $member['employeeCode'] = $matchedEmployee['employeeCode']
            ?? $member['employeeCode']
            ?? '';

        $squad[$index] = $member;
    }

    $parsed['recommendedSquad'] = $squad;
    $parsed['projectId'] = $project['id']
        ?? $project['projectId']
        ?? null;

    $parsed['projectName'] = $project['projectName'] ?? 'Project';

    $parsed['requiredHeadcount'] = (int) (
        $project['numberOfResources'] ?? 1
    );

    $parsed['deployedCount'] = count($squad);
    $parsed['modelUsed'] = "Groq ({$model})";
    $parsed['generatedAt'] = now()->toIso8601String();
    $parsed['available'] = true;
    $parsed['source'] = 'generative_ai';
    $parsed['provider'] = 'groq';
    $parsed['fallbackUsed'] = false;

    return $parsed;
}

    // =========================================================================
    // PROMPTS
    // =========================================================================

    protected function buildCandidatePrompt(array $project, array $candidate, array $evidence): string
    {
        $projJson = json_encode($project,   JSON_PRETTY_PRINT);
        $candJson = json_encode($candidate, JSON_PRETTY_PRINT);
        $evidJson = json_encode($evidence,  JSON_PRETTY_PRINT);

        return <<<PROMPT
        You are an expert AI Talent Allocation & Deployment Consultant for enterprise software projects.
        Analyze the project requirements and candidate profile below to generate a detailed recommendation.

        PROJECT SPECIFICATION:
        {$projJson}

        CANDIDATE DATA:
        {$candJson}

        EVALUATION EVIDENCE:
        {$evidJson}

        Return a valid JSON object matching this structure EXACTLY (no markdown fences):
        {
        "aiRecommendation": "Professional 2-3 sentence executive recommendation on deployment suitability.",
        "recommendedRole": "Specific role title e.g. Lead Backend Engineer",
        "deploymentReadiness": "Immediate Deployment Ready | Deployment Ready with Ramp-Up | Deployment with Mentorship | Deployment Contingency / Hold",
        "readinessScore": 88.5,
        "estimatedRampUpTime": "e.g. Immediate (0-1 week) | 1-2 weeks | 3-4 weeks",
        "keyStrengths": ["Strength 1", "Strength 2", "Strength 3"],
        "transferableSkillAnalysis": [
            {
            "requiredSkill": "Laravel",
            "candidateSkill": "PHP / Symfony",
            "similarityScore": 90,
            "transferableConcepts": "MVC patterns, ORM data access",
            "aiReasoning": "How candidate skill translates to required skill"
            }
        ],
        "skillGaps": [
            {
            "skill": "Missing skill",
            "severity": "High | Medium | Low",
            "remedy": "Recommended ramp-up action",
            "estimatedDays": 5
            }
        ],
        "deploymentRisks": [
            {
            "risk": "Identified risk",
            "mitigation": "Concrete mitigation action"
            }
        ],
        "semanticCoverageScore": 92.0
        }
        PROMPT;
            }

     protected function buildTeamPrompt(
        string $projJson,
        string $candJson
    ): string {

        return <<<PROMPT
    You are an enterprise AI Project Staffing and Squad Deployment Architect.

    Your task is to recommend the best project deployment squad using ONLY the employees provided in the TOP CANDIDATES list.

    PROJECT DETAILS:
    {$projJson}

    ELIGIBLE TOP CANDIDATES (ranked):
    {$candJson}

    STRICT EMPLOYEE SELECTION RULES:

    1. You MUST select employees exclusively from the TOP CANDIDATES JSON above.
    2. NEVER invent employee names, employee IDs or employee codes.
    3. NEVER recommend an employee who is not present in the supplied candidate list.
    4. Use the exact employeeId, employeeName and employeeCode from the candidate data.
    5. Do not modify or generate employee identity information.
    6. Do not create fictional employees, even as examples.
    7. Do not include duplicate employees.
    8. Select only employees who satisfy the project's eligibility requirements.
    9. Do not select employees whose availability or mandatory eligibility is false.
    10. If there are insufficient eligible employees, recommend only the available eligible employees. Do not fill the squad with fictional employees.
    11. Assigned roles must be based on the actual skills and experience of the selected employees.
    12. Suitability scores must be based on the supplied candidate suitability scores. Do not arbitrarily increase them.
    13. Key contributions must be based on the employee's actual skills.
    14. Use the actual employee name as the deployment roadmap lead.
    15. If no suitable employee is available, return an empty recommendedSquad array.

    ANALYSIS REQUIREMENTS:

    - Analyze technical skill coverage.
    - Analyze mandatory and preferred skill coverage.
    - Evaluate experience and proficiency.
    - Evaluate team synergy and complementary skills.
    - Recommend appropriate roles.
    - Identify uncovered mandatory skills.
    - Prepare a realistic deployment roadmap.
    - Estimate deployment readiness.

    RETURN FORMAT:

    Return valid JSON only. Do not use Markdown code fences.

    {
        "executiveSummary": "Strategic overview of the recommended deployment squad",
        "teamSynergyScore": 94.5,
        "mandatoryCoveragePercentage": 100.0,
        "preferredCoveragePercentage": 85.0,
        "uncoveredMandatorySkills": [],
        "recommendedSquad": [
            {
                "employeeId": 0,
                "employeeName": "Exact name from candidate list",
                "employeeCode": "Exact code from candidate list",
                "assignedRole": "Lead Architect",
                "suitabilityScore": 92.0,
                "experience": 6.5,
                "keyContributions": ["Laravel", "REST API", "Docker"],
                "readiness": "Immediate Deploy"
            }
        ],
        "deploymentRoadmap": [
            {
                "phase": "Phase 1: Architecture Setup",
                "timeline": "Sprint 1-2",
                "focus": "Key milestone focus",
                "lead": "Exact employee name from candidate list"
            }
        ],
        "deploymentHealth": "Optimal"
    }

    IMPORTANT:
    The employee details in the JSON output MUST match the supplied TOP CANDIDATES data exactly.
    The example values above are placeholders, not actual employees.
    Never return Alice Sharma, Rahul Patel, or any other employee unless that employee exists in the supplied candidate list.
    PROMPT;
    }

    // =========================================================================
    // BUILT-IN HEURISTIC FALLBACK ENGINE
    // =========================================================================

    /**
     * Built-in heuristic recommendation for an individual candidate.
     * Used when Groq API is unavailable or returns invalid response.
     */
    protected function buildHeuristicCandidateRecommendation(array $project, array $candidate, array $evidence = []): array
    {
        $employee    = $candidate['employee'] ?? $candidate;
        $empName     = $employee['employeeName'] ?? 'The candidate';
        $empCode     = $employee['employeeCode'] ?? '';
        $empExp      = (float) ($employee['employeeExperience'] ?? $employee['experience'] ?? 0);
        $reqExp      = (float) ($employee['requiredExperience']  ?? $project['minExperience'] ?? 0);
        $empProf     = (float) ($employee['employeeProficiency'] ?? $employee['proficiency'] ?? 0);
        $reqProf     = (float) ($employee['requiredProficiency'] ?? $project['minProficiency'] ?? 0);
        $capacity    = (float) ($employee['remainingCapacity']   ?? 100);
        $suitability = (float) ($candidate['suitabilityScore']   ?? 0);
        $constMet    = (bool)  ($candidate['mandatoryConstraintsMet'] ?? true);

        $projectName = $project['projectName'] ?? 'Project Deployment';
        $domain      = $project['businessDomain'] ?? 'Enterprise Software';

        $empSkills = $this->extractSkillsList($employee['employeeSkills'] ?? $employee['primarySkill'] ?? []);
        $reqSkills = $this->extractSkillsList($project['mandatorySkills'] ?? []);
        $semantic = $this->matchSkillsSemantically($reqSkills, $empSkills);
       $recommendedRole = $this->RecommendedRole($empSkills);

        // Deployment readiness
        if (!$constMet) {
            $readiness = 'Deployment Contingency / Hold';
            $readinessScore = max(20, min(50, $suitability));
            $rampUp = '4-8 weeks';
            $role   = 'Future Development Candidate';
        } elseif ($suitability >= 82) {
            $readiness = 'Immediate Deployment Ready';
            $readinessScore = max(85, min(98, $suitability + 5));
            $rampUp = 'Immediate (0-1 week)';
           // $role   = $empExp >= 5 ? 'Technical Lead / Senior Architect' : 'Project Manager';
           // $role = $recommendedRole;
           // $role = $empExp >=5 ? 'Technical lead':$recommendedRole;
             $role = $this->EmployeeRole($employee, $suitability);
        } elseif ($suitability >= 65) {
            $readiness = 'Deployment Ready with Ramp-Up';
            $readinessScore = max(70, min(84, $suitability));
            $rampUp = '1-2 weeks';
           // $role   = 'Module Owner / Fullstack Developer';
          //  $role = $recommendedRole;
         // $role = $empExp >= 5 ?'Senior'.$recommendedRole:$recommendedRole;
           $role = $this->EmployeeRole($employee, $suitability);
        } else {
            $readiness = 'Deployment with Mentorship';
            $readinessScore = max(50, min(69, $suitability));
            $rampUp = '2-4 weeks';
           // $role   = 'Associate Developer / Support Engineer';
           // $role = $recommendedRole;
            $role = $this->EmployeeRole($employee, $suitability);
        }

        // Strengths
        $strengths = [];
        if (!empty($semantic['directMatches'])) {
            $strengths[] = 'Strong alignment in mandatory technologies: ' . implode(', ', array_slice($semantic['directMatches'], 0, 3)) . '.';
        }
        if ($empExp >= $reqExp) {
            $strengths[] = "Experience ({$empExp} yrs) meets or exceeds the {$reqExp} yr project requirement.";
        }
        if ($empProf >= $reqProf) {
            $strengths[] = "Proficiency rating ({$empProf}/5) satisfies the project threshold ({$reqProf}/5).";
        }
        if (!empty($semantic['transferableMatches'])) {
            $t = $semantic['transferableMatches'][0];
            $strengths[] = "Lateral adaptability: proficiency in {$t['candidateSkill']} bridges requirement for {$t['requiredSkill']}.";
        }
        if (!empty($evidence['certifications'])) {
            $strengths[] = "Validated industry certifications on file: {$evidence['certifications']}.";
        }
        if ($capacity >= 80) {
            $strengths[] = "High availability ({$capacity}% remaining capacity) enables prompt onboarding.";
        }
        if (empty($strengths)) {
            $strengths[] = 'Demonstrates foundational capabilities for project deployment.';
        }

        // Skill gaps
        $skillGaps = [];
        foreach ($semantic['unmatchedSkills'] as $missing) {
            $skillGaps[] = [
                'skill'        => $missing,
                'severity'     => 'Mandatory Requirement',
                'remedy'       => "Targeted hands-on onboarding lab and pairing with senior engineer on {$missing}.",
                'estimatedDays'=> 10,
            ];
        }
        foreach ($semantic['transferableMatches'] as $t) {
            $skillGaps[] = [
                'skill'        => $t['requiredSkill'],
                'severity'     => 'Transferable Synergy',
                'remedy'       => "Leverage {$t['candidateSkill']} to cross-train on {$t['requiredSkill']}.",
                'estimatedDays'=> 5,
            ];
        }

        // Risks
        $risks = [];
        if (!empty($semantic['unmatchedSkills'])) {
            $risks[] = [
                'risk'       => 'Knowledge gap in mandatory stack: ' . implode(', ', array_slice($semantic['unmatchedSkills'], 0, 2)) . '.',
                'mitigation' => 'Sprint-zero technical ramp-up with architectural reference templates.',
            ];
        }
        if ($capacity < 50) {
            $risks[] = [
                'risk'       => "Constrained availability ({$capacity}% remaining capacity).",
                'mitigation' => 'Adjust allocation % or offload non-critical tasks from concurrent projects.',
            ];
        }
        if ($empExp < $reqExp) {
            $risks[] = [
                'risk'       => "Experience delta: {$empExp} yrs actual vs {$reqExp} yrs required.",
                'mitigation' => 'Pair with a senior mentor for the first 2 development iterations.',
            ];
        }
        if (empty($risks)) {
            $risks[] = [
                'risk'       => 'Low operational deployment risk.',
                'mitigation' => 'Standard sprint onboarding and routine backlog grooming.',
            ];
        }

        // Executive narrative
        $narrative = "{$empName} ({$empCode}) presents a {$readiness} profile for '{$projectName}' ({$domain}). ";
        if ($constMet && $suitability >= 75) {
            $narrative .= "With a composite suitability of {$suitability}%, the profile reflects robust execution readiness. ";
        } elseif ($constMet) {
            $narrative .= "With a score of {$suitability}%, the candidate benefits from focused sprint-0 ramp-up. ";
        } else {
            $narrative .= "Mandatory skill disparities require targeted upskilling prior to unassisted deployment. ";
        }
        if (!empty($semantic['transferableMatches'])) {
            $t = $semantic['transferableMatches'][0];
            $narrative .= "AI skill-mapping identified transferability: mastery of {$t['candidateSkill']} facilitates adaptation to {$t['requiredSkill']}. ";
        }
        $narrative .= "Recommended role: '{$role}' with estimated onboarding horizon of {$rampUp}.";

        return [
            'aiRecommendation'        => $narrative,
            'recommendedRole'         => $role,
            'deploymentReadiness'     => $readiness,
            'readinessScore'          => round($readinessScore, 1),
            'estimatedRampUpTime'     => $rampUp,
            'keyStrengths'            => array_slice($strengths, 0, 4),
            'skillGaps'               => $skillGaps,
            'deploymentRisks'         => $risks,
            'transferableSkillAnalysis' => $semantic['transferableMatches'],
            'semanticCoverageScore'   => $semantic['effectiveCoverageScore'],
            'modelUsed'               => 'Built-in Heuristic Engine (Groq fallback)',
            'generatedAt'             => now()->toIso8601String(),
        ];
    }

    /**
     * Built-in heuristic team squad recommendation.
     * Used when Groq API is unavailable or returns invalid response.
     */
    protected function buildHeuristicTeamRecommendation(array $project, array $rankedCandidates): array
    {
        $requiredCount = max(1, (int) ($project['numberOfResources'] ?? 1));
        $projectName   = $project['projectName'] ?? 'Project';
        $domain        = $project['businessDomain'] ?? 'Enterprise Solutions';

        $eligible = array_values(array_filter($rankedCandidates, fn ($c) => ($c['mandatoryConstraintsMet'] ?? false) === true));
        if (empty($eligible)) {
            $eligible = $rankedCandidates;
        }

        $selectedSquad   = array_slice($eligible, 0, $requiredCount);
        $backupCandidates = array_slice($eligible, $requiredCount, 3);

        $avgScore       = 0.0;
        $totalExp       = 0.0;
        $covMandatory   = [];
        $covPreferred   = [];

        $reqMandatory = $this->extractSkillsList($project['mandatorySkills'] ?? []);
        $reqPreferred = $this->extractSkillsList($project['preferredSkills'] ?? []);

        $squadMembers = [];
        foreach ($selectedSquad as $idx => $candidate) {
            $emp    = $candidate['employee'] ?? $candidate;
            $name   = $emp['employeeName'] ?? "Resource #" . ($idx + 1);
            $code   = $emp['employeeCode'] ?? '';
            $score  = (float) ($candidate['suitabilityScore'] ?? 0);
            $exp    = (float) ($emp['employeeExperience'] ?? $emp['experience'] ?? 0);

           // $skills = $this->extractSkillsList($emp['employeeSkills'] ?? $emp['primarySkill'] ?? []);

           $skills = $this->extractSkillsList(
            $emp['employeeSkills']
                ?? $emp['skills']
                ?? $emp['technologies']
                ?? $emp['primarySkill']
                ?? []
        );

            $avgScore += $score;
            $totalExp += $exp;

            $covMandatory = array_unique(array_merge($covMandatory, array_intersect($reqMandatory, $skills)));
            $covPreferred = array_unique(array_merge($covPreferred, array_intersect($reqPreferred, $skills)));

          /*   $role = match (true) {
                $idx === 0 && $exp >= 5             => 'Squad Lead / Lead Architect',
                $idx === 0                          => 'Senior Module Lead',
                $idx === count($selectedSquad) - 1  => 'Integration & QA Specialist',
                default                             => 'Core Feature Engineer',
            }; */

            $role = $this->EmployeeRole($emp, $score);
           // $role = $this->formatRecommendedRole($role, $emp);
            $role = $this->formatRecommendedRole($role, [
                'employeeSkills' => $skills,
            ]);

            $squadMembers[] = [
                'employeeId'      => $emp['employeeId'] ?? $emp['id'] ?? null,
                'employeeName'    => $name,
                'employeeCode'    => $code,
                'assignedRole'    => $role,
                'suitabilityScore'=> $score,
                'experience'      => $exp,
                'keyContributions'=> array_slice($skills, 0, 3),
                'readiness'       => $score >= 80 ? 'Immediate Deploy' : 'Fast Ramp-up',
            ];
        }

        $squadCount         = max(1, count($selectedSquad));
        $teamSynergyScore   = min(99.0, max(50.0, round(($avgScore / $squadCount) * 0.9 + 10, 1)));
        $mandCoveragePct    = !empty($reqMandatory) ? round(count($covMandatory) / count($reqMandatory) * 100, 1) : 100;
        $prefCoveragePct    = !empty($reqPreferred) ? round(count($covPreferred) / count($reqPreferred) * 100, 1) : 100;

        $leadName = $squadMembers[0]['employeeName'] ?? 'the primary candidate';
        $summary  = "The AI Deployment Engine recommends an optimized {$requiredCount}-person squad for '{$projectName}' ({$domain}), "
                  . "yielding a Team Synergy Index of {$teamSynergyScore}%. Helmed by {$leadName}, this composition covers "
                  . "{$mandCoveragePct}% of mandatory competencies and {$prefCoveragePct}% of preferred capabilities.";

        return [
            'projectId'                  => $project['id'] ?? null,
            'projectName'                => $projectName,
            'requiredHeadcount'          => $requiredCount,
            'deployedCount'              => count($selectedSquad),
            'teamSynergyScore'           => $teamSynergyScore,
            'mandatoryCoveragePercentage'=> $mandCoveragePct,
            'preferredCoveragePercentage'=> $prefCoveragePct,
            'uncoveredMandatorySkills'   => array_values(array_diff($reqMandatory, $covMandatory)),
            'executiveSummary'           => $summary,
            'recommendedSquad'           => $squadMembers,
            'backupOptions'              => array_map(fn ($c) => [
                'employeeId'     => ($c['employee'] ?? $c)['employeeId'] ?? ($c['employee'] ?? $c)['id'] ?? null,
                'employeeName'   => ($c['employee'] ?? $c)['employeeName'] ?? 'Candidate',
                'suitabilityScore' => $c['suitabilityScore'] ?? 0,
                'note'           => 'Viable alternative if primary candidate allocation changes.',
            ], $backupCandidates),
            'deploymentRoadmap'          => [
                ['phase' => 'Phase 1: Architecture & Onboarding', 'timeline' => 'Sprint 1-2',  'focus' => 'Environment setup, repo baseline, knowledge transfer, backlog grooming.',  'lead' => $squadMembers[0]['employeeName'] ?? 'Lead'],
                ['phase' => 'Phase 2: Core Feature Development',  'timeline' => 'Sprint 3-6',  'focus' => 'Primary story delivery, REST API development, unit testing, integration.',   'lead' => 'All Squad Members'],
                ['phase' => 'Phase 3: Hardening & Rollout',       'timeline' => 'Sprint 7-8',  'focus' => 'Performance profiling, E2E regression, CI/CD pipeline, stakeholder sign-off.', 'lead' => $squadMembers[min(1, count($squadMembers) - 1)]['employeeName'] ?? 'Deployment Lead'],
            ],
            'deploymentHealth'           => $teamSynergyScore >= 80 ? 'Optimal' : ($teamSynergyScore >= 65 ? 'Stable' : 'Caution'),
            'modelUsed'                  => 'Built-in Heuristic Engine (Groq fallback)',
            'generatedAt'               => now()->toIso8601String(),
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Parse JSON from LLM response — strips markdown fences if present.
     */
    protected function parseJsonResponse(?string $content): ?array
    {
        if (empty($content)) {
            return null;
        }

        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
        $clean = preg_replace('/\s*```$/', '', $clean);

        $parsed = json_decode($clean, true);
        return is_array($parsed) ? $parsed : null;
    }

    /**
     * Safely extract a flat list of skill strings from various input formats.
     */
    protected function extractSkillsList(mixed $skills): array
    {
        if (is_array($skills)) {
            $list = $skills;
        } elseif (is_string($skills)) {
            $decoded = json_decode($skills, true);
            $list    = is_array($decoded) ? $decoded : preg_split('/[,;\n]+/', $skills);
        } else {
            return [];
        }

        $clean = [];
        foreach ($list as $s) {
            $t = strtolower(trim((string) $s));
            if ($t !== '') {
                $clean[] = $t;
            }
        }

        return array_values(array_unique($clean));
    }


  private function RecommendedRole(array $skills): string
  {
    $skills = array_map(fn ($skill) => strtolower(trim((string) $skill)),
        $skills
    );

    $frontendSkills = [
        'angular',
        'react',
        'react.js',
        'reactjs',
        'vue',
        'vue.js',
        'vuejs',
        'javascript',
        'typescript',
        'html',
        'css',
        'next.js',
        'nextjs'
    ];

    $backendSkills = [
        'php',
        'laravel',
        'node.js',
        'nodejs',
        'express',
        'express.js',
        '.net core',
        'asp.net core',
        'c#',
        'java',
        'spring boot',
        'python',
        'django',
        'fastapi',
        'sql',
        'mysql',
        'postgresql',
        'mongodb'
    ];

    $hasFrontend = false;
    $hasBackend = false;

    foreach ($skills as $skill) {
        if (in_array($skill, $frontendSkills, true)) {
            $hasFrontend = true;
        }

        if (in_array($skill, $backendSkills, true)) {
            $hasBackend = true;
        }
    }

    if ($hasFrontend && $hasBackend) {
        return 'Full Stack Developer';
    }

    if ($hasBackend) {
        return 'Backend Engineer';
    }

    if ($hasFrontend) {
        return 'Frontend Engineer';
    }

    return 'Project Manager';
}


  private function EmployeeRole(
    array $employee,
    float $suitabilityScore
): string {
    $experience = (float) (
        $employee['employeeExperience']
        ?? $employee['experience']
        ?? 0
    );

    $skills = $this->extractSkillsList(
        $employee['employeeSkills']
            ?? $employee['primarySkill']
            ?? []
    );

    $recommendedRole = $this->RecommendedRole($skills);

    if ($suitabilityScore >= 65 && $experience >= 5) {
        $recommendedRole = $suitabilityScore >= 82
            ? 'Technical Lead / Senior Architect - ' . $recommendedRole
            : 'Senior ' . $recommendedRole;
    }

    return $this->formatRecommendedRole(
        $recommendedRole,
        $employee
    );
}


protected function formatRecommendedRole(string $role, array $employee): string
{
    // Remove any existing parenthesized suffix to prevent duplication.
    $role = trim($role);
    $role = preg_replace('/\s*\([^()]*\)\s*$/', '', $role);

    // Extract employee technologies.
    $skills = $this->extractSkillsList(
        $employee['skills']
            ?? $employee['technologies']
            ?? $employee['primarySkill']
            ?? []
    );

    // Remove duplicate skills.
    $skills = array_values(array_unique($skills));

    if (empty($skills)) {
        return $role;
    }

    return $role . ' (' . implode(', ', $skills) . ')';
}


}
