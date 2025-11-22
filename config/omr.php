<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OMR PDF Generation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for generating OMR (Optical Mark Recognition) PDF documents.
    | These settings control how PDFs are generated in chunks to optimize
    | memory usage and processing time.
    |
    */

    'pdf_generation' => [
        /*
        | Number of pages to generate per chunk when creating large PDF batches.
        | Larger values = faster generation but more memory usage.
        | Smaller values = slower but more stable for limited resources.
        | Recommended: 100-500 depending on server capacity.
        */
        'chunk_size' => env('OMR_PDF_CHUNK_SIZE', 100),

        /*
        | Threshold to trigger background job processing.
        | If total folios exceed this number, PDFs will be generated via queue jobs.
        | Below this threshold, PDFs are generated synchronously.
        */
        'job_threshold' => env('OMR_PDF_JOB_THRESHOLD', 100),

        /*
        | Maximum memory limit for PDF generation (in MB).
        | Applied to both synchronous and job-based generation.
        */
        'memory_limit' => env('OMR_PDF_MEMORY_LIMIT', 512),

        /*
        | Maximum execution time for PDF generation (in seconds).
        | Applied to both synchronous and job-based generation.
        */
        'execution_time' => env('OMR_PDF_EXECUTION_TIME', 1800),

        /*
        | Timeout for Browsershot/Puppeteer navigation (in seconds).
        | Increase if PDFs contain many pages or complex layouts.
        */
        'browsershot_timeout' => env('OMR_PDF_BROWSERSHOT_TIMEOUT', 300),

        /*
        | Scale factor for PDF rendering (0.1 to 1.0).
        | Adjust to fine-tune PDF dimensions and quality.
        */
        'scale_factor' => env('OMR_PDF_SCALE_FACTOR', 0.96),
    ],
];
