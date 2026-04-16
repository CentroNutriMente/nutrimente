<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Applied to all routes so that AJAX requests from the SPA (which may
    | run on a different origin in some deployment topologies) can include
    | session credentials.  CORS_ALLOWED_ORIGINS can be a comma-separated
    | list of additional origins; APP_URL is always included automatically.
    |
    */

    'paths' => ['*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map('trim', array_merge(
        // Always allow the configured app URL (same-origin requests are
        // unaffected; this only matters for genuine cross-origin scenarios).
        [env('APP_URL', 'http://localhost')],
        // Optional extra origins, e.g. a custom domain in front of Render.
        env('CORS_ALLOWED_ORIGINS') ? explode(',', env('CORS_ALLOWED_ORIGINS')) : [],
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    // Must be true so browsers accept the response when axios sends
    // withCredentials = true (session cookie).
    'supports_credentials' => true,

];
