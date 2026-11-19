<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    | Supported: 'gemini', 'openai', 'ollama', 'builtin'
    | Set AI_PROVIDER in .env to override. When OPENAI_API_KEY is set and
    | AI_PROVIDER is not explicitly configured, the service auto-selects 'openai'.
    |
    */
    'provider' => env('AI_PROVIDER', 'builtin'),

   'gemini' => [
        'api_key'  => env('GEMINI_API_KEY'),
        'model'    => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/',
        'timeout'  => env('AI_TIMEOUT', 60),
    ],

    // OpenAI-compatible endpoint (works with OpenAI, Groq, etc.)
    'openai' => [
        'api_key'  => env('OPENAI_API_KEY'),
        'model'    => env('OPENAI_MODEL', 'openai/gpt-oss-20b'),
        'endpoint' => env('AI_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions'),
        'timeout'  => env('AI_TIMEOUT', 30),
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model'    => env('OLLAMA_MODEL', 'llama3'),
        'timeout'  => env('AI_TIMEOUT', 20),
    ],

    'cache_ttl' => env('AI_CACHE_TTL', 3600), // Cache  for 1 hour
];
