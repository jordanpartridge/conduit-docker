<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Docker Configuration
    |--------------------------------------------------------------------------
    */
    'image_name' => env('DOCKER_IMAGE_NAME', config('app.name', 'laravel-app')),
    'registry' => env('DOCKER_REGISTRY', 'localhost:5000'),

    /*
    |--------------------------------------------------------------------------
    | Environment Settings
    |--------------------------------------------------------------------------
    */
    'environments' => [
        'development' => [
            'compose_file' => 'docker-compose.yml',
            'dockerfile' => 'Dockerfile',
            'health_check_retries' => 3,
        ],
        'staging' => [
            'compose_file' => 'docker-compose.staging.yml',
            'dockerfile' => 'Dockerfile.production',
            'health_check_retries' => 5,
        ],
        'production' => [
            'compose_file' => 'docker-compose.prod.yml',
            'dockerfile' => 'Dockerfile.production',
            'health_check_retries' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Checks
    |--------------------------------------------------------------------------
    */
    'health_checks' => [
        'database' => true,
        'redis' => true,
        'storage' => true,
        'external_services' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployment Settings
    |--------------------------------------------------------------------------
    */
    'deployment' => [
        'backup_count' => 5,
        'timeout' => 300,
        'rollback_on_failure' => true,
    ],
];
