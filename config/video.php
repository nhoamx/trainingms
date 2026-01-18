<?php

/**
 * Video Configuration
 *
 * Define the base URL and settings for video files used in quizzes.
 * This follows the same pattern as the audio system for consistency.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Video Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL where video files are stored. This can be:
    | - A local storage path: '/storage/video'
    | - A CDN URL: 'https://cdn.example.com/video'
    | - An S3 URL: 'https://s3.amazonaws.com/bucket/video'
    |
    | The frontend will append the question type and ID to construct full URLs:
    | {base_url}/{question_type}/{question_id}.mp4
    |
    | Example: /storage/video/general/0.mp4
    |
    */
    'base_url' => env('VIDEO_BASE_URL', '/storage/video'),

    /*
    |--------------------------------------------------------------------------
    | Video Storage Path
    |--------------------------------------------------------------------------
    |
    | The local storage disk where video files are stored (used for uploads/management)
    |
    */
    'storage_disk' => env('VIDEO_STORAGE_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Supported Video Formats
    |--------------------------------------------------------------------------
    |
    | List of supported video formats for validation
    |
    */
    'supported_formats' => ['mp4', 'webm', 'mov', 'avi'],

    /*
    |--------------------------------------------------------------------------
    | Maximum Video File Size (in KB)
    |--------------------------------------------------------------------------
    |
    | Maximum allowed size for uploaded video files
    |
    */
    'max_file_size' => 50000, // 50MB

    /*
    |--------------------------------------------------------------------------
    | Video Structure
    |--------------------------------------------------------------------------
    |
    | Define how video files are organized by question type
    | This maps question types to their directory structure
    |
    */
    'question_types' => [
        'general' => [
            'path' => 'general',
            'description' => 'Video files for Reference III general questions',
        ],
        'conditional' => [
            'path' => 'conditional',
            'description' => 'Video files for conditional follow-up questions',
        ],
        'traumatic' => [
            'path' => 'traumatic',
            'description' => 'Video files for traumatic events questions',
        ],
        'cisneros' => [
            'path' => 'cisneros',
            'description' => 'Video files for Escala Cisneros questions',
        ],
        'referencia_i' => [
            'path' => 'referencia_i',
            'description' => 'Video files for Reference I follow-up questions',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Availability
    |--------------------------------------------------------------------------
    |
    | Control whether videos are enabled globally
    | Can be overridden per question or section
    |
    */
    'enabled' => env('VIDEO_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Video Player Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for the video player component
    |
    */
    'player' => [
        'controls' => true,
        'autoplay' => false,
        'preload' => 'metadata', // none, metadata, auto
        'playback_rates' => [0.5, 0.75, 1, 1.25, 1.5, 2],
        'default_volume' => 0.8,
    ],
];
