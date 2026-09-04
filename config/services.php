<?php

use App\Support\ExcludedProjectDirectories;
use App\Support\ProjectContainerDirectories;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://ollama:11434'),
        'embed_model' => env('OLLAMA_EMBED_MODEL', 'bge-m3'),
    ],

    'qdrant' => [
        'base_url' => env('QDRANT_BASE_URL', 'http://qdrant:6333'),
        'collection' => env('QDRANT_COLLECTION', 'project_manager_content'),
    ],

    'scanner' => [
        'base_path' => env('SCAN_BASE_PATH', '/var/www/host_projects'),

        /*
         * The three lists below default to the constants in App\Support so the
         * values live in one place, and each accepts a comma-separated env var
         * that *adds* to the default — a host only needs to name what its own
         * stack adds (e.g. SCAN_EXCLUDED_EXTRA=writable,var for CodeIgniter and
         * Symfony), never to restate the whole list.
         */

        // Dependency/build output: skipped when measuring a project's own tree.
        'excluded_directories' => array_values(array_unique(array_merge(
            ExcludedProjectDirectories::DIRECTORIES,
            array_filter(array_map('trim', explode(',', (string) env('SCAN_EXCLUDED_EXTRA', ''))))
        ))),

        // Directories that hold projects instead of being one: descend one level.
        'container_directories' => array_values(array_unique(array_merge(
            ProjectContainerDirectories::DIRECTORIES,
            array_filter(array_map('trim', explode(',', (string) env('SCAN_CONTAINERS_EXTRA', ''))))
        ))),

        // Entries under base_path that are never imported at all (blacklist).
        'ignored_entries' => array_values(array_unique(array_merge(
            ['project-manager'],
            array_filter(array_map('trim', explode(',', (string) env('SCAN_IGNORED_EXTRA', ''))))
        ))),
    ],

];
