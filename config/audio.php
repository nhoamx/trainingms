<?php

/**
 * Audio Configuration
 *
 * Define the base URL and settings for audio files used in quizzes.
 *
 * NOTE: This is a temporary configuration file. Once the audio system is fully
 * migrated to the backend (see GitHub Issue #[TODO]), the audio URLs should be
 * generated dynamically from the database based on question associations.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Audio Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL where audio files are stored. This can be:
    | - A local storage path: '/storage/audio'
    | - A CDN URL: 'https://cdn.example.com/audio'
    | - An S3 URL: 'https://s3.amazonaws.com/bucket/audio'
    |
    | The frontend will append the question type and ID to construct full URLs:
    | {base_url}/{question_type}/{question_id}.mp3
    |
    | Example: /storage/audio/general/question_1.mp3
    |
    */
    'base_url' => env('AUDIO_BASE_URL', '/storage/audio'),

    /*
    |--------------------------------------------------------------------------
    | Audio Storage Path
    |--------------------------------------------------------------------------
    |
    | The local storage disk where audio files are stored (used for uploads/management)
    |
    */
    'storage_disk' => env('AUDIO_STORAGE_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Supported Audio Formats
    |--------------------------------------------------------------------------
    |
    | List of supported audio formats for validation
    |
    */
    'supported_formats' => ['mp3', 'wav', 'ogg', 'm4a'],

    /*
    |--------------------------------------------------------------------------
    | Maximum Audio File Size (in KB)
    |--------------------------------------------------------------------------
    |
    | Maximum allowed size for uploaded audio files
    |
    */
    'max_file_size' => 5000, // 5MB

    /*
    |--------------------------------------------------------------------------
    | Audio Structure
    |--------------------------------------------------------------------------
    |
    | Define how audio files are organized by question type
    | This maps question types to their directory structure
    |
    | TODO: Once migrated to backend, this should be replaced with database
    | associations between questions and audio tracks
    |
    */
    'question_types' => [
        'general' => [
            'path' => 'general',
            'description' => 'Audio files for Reference III general questions',
        ],
        'conditional' => [
            'path' => 'conditional',
            'description' => 'Audio files for conditional follow-up questions',
        ],
        'traumatic' => [
            'path' => 'traumatic',
            'description' => 'Audio files for traumatic events questions',
        ],
        'cisneros' => [
            'path' => 'cisneros',
            'description' => 'Audio files for Escala Cisneros questions',
        ],
        'referencia_i' => [
            'path' => 'referencia_i',
            'description' => 'Audio files for Reference I follow-up questions',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audio Availability
    |--------------------------------------------------------------------------
    |
    | Whether audio playback is enabled in quizzes
    | Can be overridden at organization level in the future
    |
    */
    'enabled' => env('AUDIO_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Fallback Behavior
    |--------------------------------------------------------------------------
    |
    | What to do when an audio file is not found:
    | - 'silent': Don't show audio player (current behavior)
    | - 'error': Show error message to user
    | - 'default': Play a default audio file
    |
    */
    'fallback_mode' => env('AUDIO_FALLBACK_MODE', 'silent'),

];
