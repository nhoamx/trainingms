# Copilot Instructions for `trainingms`

This document provides guidance for AI coding agents working on the `trainingms` project. It outlines the architecture, workflows, conventions, and integration points to help agents be productive immediately.

## Project Overview

`trainingms` is a Laravel 11 + Inertia.js + Vue 3 application for managing **psychological workplace assessments** under multiple Mexican regulatory instruments. This system handles both online digital evaluations and OCR-processed paper forms, supporting:

- **NOM-035-STPS-2018**: Psychosocial risk factors identification, analysis, and prevention
- **Clima Laboral (Work Climate)**: Organizational climate and work environment assessment

The platform processes evaluations through dual workflows: online quiz submissions and OCR-scanned paper forms.

### Core Domain Concepts

#### Regulatory Instruments
- **NOM-035-STPS-2018**: Official Mexican standard for psychosocial risk factors in the workplace - identification, analysis, and prevention
  - Different requirements by organization size (≤15, 16-50, >50 workers)
  - Instruments: Guide I (PTSD), Escala Cisneros (Mobbing), Reference III/V (Workplace factors)
- **Clima Laboral (Work Climate)**: Organizational climate assessment using Likert scales
  - Evaluates work environment, leadership, and organizational culture
  - Scoring system with levels: Muy desfavorable, Desfavorable, Medio, Favorable, Muy favorable
- **NOM-002-STPS-2010**: Norma Oficial Mexicana para la Prevención y Protección contra Incendios en Centros de Trabajo

#### Core Entities
- **Organizations**: Companies conducting evaluations with their employees
- **Quizzes**: Temporary evaluation links with expiration dates for online assessments
- **Folios**: Unique identifiers (4-digit strings) for paper-based evaluations processed via OCR
- **PaperEvaluation**: OCR-processed bubble sheet evaluations (supports NOM-035 and Likert/Clima Laboral)
- **SubmissionStatus**: Tracks online quiz submission processing state (pending, processing, completed, failed)
- **DemographicData**: Normalized demographic information linked to evaluations
- **EvaluationComment**: Comments/observations for individual evaluations
- **EvaluationCustomField**: Dynamic custom fields associated with evaluations

### Application Architecture

- **Backend** (`app/`): Laravel 11 with domain models (Quiz, Evaluation, PaperEvaluation, Organization, Folio, etc.)
- **Frontend** (`resources/js/`): Vue 3 + Inertia.js with modular quiz components in `Components/Quiz/`
- **OCR Processing** (`docker/`): Python-based bubble sheet detection for paper forms (outputs JSON by folio)
- **Configuration** (`config/`): Domain-specific question sets and demographic data structures
- **Database** (`database/`): Psychological evaluation data with JSON storage for flexible questionnaire responses
- **Services Layer** (`app/Services/`): 19 specialized services for reports, scoring, caching, and data processing
- **Queued Jobs** (`app/Jobs/`): Async processing for reports, imports, OCR, and cache warming
- **Exports** (`app/Exports/`): Excel/CSV exports using Laravel Excel with multi-sheet support

## Developer Workflows

### Database Operations
- Run migrations with `php artisan migrate`
- Seed with `php artisan db:seed --class=RolesSeeder` (roles use `firstOrCreate` pattern)
- Models use UUIDs where applicable (`Evaluation` model uses `HasUuids` trait)
- Personal IDs are always 4-digit zero-padded strings (see `Evaluation::setPersonalIdAttribute`)

### OCR Processing Workflow
- Paper forms are processed via Docker container (`docker/main.py`)
- Python scripts detect bubble sheets and output JSON results by folio ID to `docker/output/`
- Results are imported into Laravel via JSON parsing into `Evaluation->data` field or `PaperEvaluation->raw_data`
- Config files in `docker/config/` define bubble positions for different form layouts

### Frontend Development
- Vue 3 components in `resources/js/Components/Quiz/` are modular and reusable
- Use `npm run dev` for development, `npm run build` for production
- Quiz components follow composition API patterns with `v-model` (see `Components/Quiz/README.md`)
- All components use Tailwind CSS - check sibling components for consistent styling patterns

### Testing
- Run tests with `php artisan test`
- Use `php artisan test --filter=TestName` for specific tests
- All database tests use `DatabaseTransactions` trait
- Feature tests are in `tests/Feature/` - includes cache, export, import, and integration tests
- Must run `vendor/bin/pint --dirty` before finalizing changes (code formatting enforcement)

### Async Job Processing
- 9 queued jobs handle heavy operations: report generation, imports, OCR processing, cache warming
- Use `ProcessQuizSubmission` for online quiz submissions (creates `SubmissionStatus` records)
- Use `ProcessPaperEvaluation` for OCR result imports
- Use `GenerateWordReport` for Word document generation (queued, not synchronous)
- Use `WarmOrganizationReportCache` to precompute expensive reports

## Project-Specific Conventions

### Domain-Specific Patterns
- **Multi-Instrument Support**: System now supports multiple regulatory frameworks
  - **NOM-035**: Psychosocial risk assessment with Reference Guides I, III, V and Escala Cisneros
  - **Clima Laboral**: Work climate assessment using Likert scales (`likert_answers` in `PaperEvaluation`)
  - Both can be processed from same paper form or separate online quizzes
- **Question Configuration**: All questionnaires defined in `config/` files:
  - NOM-035: `guide_i_questions.php`, `escala_cisneros.php`, `referencia_iii.php`, `referencia_v.php`
  - Clima Laboral: `likert-value.php` (scoring and interpretation levels)
  - Demographics: `referencia_v.php` (edad, estado civil, nivel estudios, datos laborales)
- **JSON Data Storage**: 
  - `PaperEvaluation->raw_data`: Complete OCR results including quiz_id, evaluation_type_code
  - `PaperEvaluation->likert_answers`: Clima Laboral responses
  - `PaperEvaluation->referencia_i_answers`, `referencia_iii_answers`: NOM-035 responses
  - `PaperEvaluation->demographic_data`: Normalized demographic information
- **Quiz Types**: Flags determine evaluation type (`is_reduced`, `is_cisneros` on Quiz model)
- **Temporary URLs**: Quizzes have expiring URLs (`expires_at`, `scopeActive` filters active non-expired)

### Cache System Architecture (Critical!)
- **Centralized Cache Service**: `OrganizationReportCacheService` manages all organization report caching
- **Three Cache Keys**: `org_report_list_{id}`, `org_report_missing_folios_{id}`, `org_report_likert_{id}`
- **Auto-Invalidation via Observers**: Changes to `PaperEvaluation`, `DemographicData`, `EvaluationComment`, or `EvaluationCustomField` automatically invalidate organization caches
- **Cache Warming**: `WarmOrganizationReportCache` job precomputes expensive reports
- See `docs/CACHE_SYSTEM_EXPLANATION.md` for complete architecture diagrams

### Vue Component Architecture
- Quiz components use composition API with `v-model` patterns for two-way binding
- Modular components in `Components/Quiz/` for reusability between quiz types:
  - `ProgressBar.vue`, `ViewModeToggle.vue`, `NavigationButtons.vue` (UI controls)
  - `PersonalDataSection.vue`, `LaborDataSection.vue` (demographic collection)
  - `TraumaticEventsSection.vue`, `GeneralQuestionsSection.vue`, `ConditionalQuestionsSection.vue` (question types)
- Data collection components handle personal/labor demographics separately
- Navigation and progress components provide consistent UX across quiz flows
- All emit `update:modelValue` for parent communication

### Model Conventions
- Use `casts()` method for model casting (Laravel 11 pattern), not `$casts` property
- UUID primary keys where applicable (uses `HasUuids` trait)
- JSON casting for flexible data structures (`data`, `raw_data`, `demographic_data` fields)
- Scope methods for common queries (`scopeActive` on Quiz, check other models for similar patterns)
- Always use constructor property promotion for dependencies

### Deprecated Models (DO NOT USE)
- **`Evaluation`**: Replaced by `PaperEvaluation` for paper forms and `SubmissionStatus` for online submissions
  - Legacy model with recursive answer processing
  - New code should use `PaperEvaluation` (for OCR results) or `SubmissionStatus` (for quiz submissions)
- **`Question`**: Replaced by `SubmissionStatus` for a flatter, more performant structure
  - Old model had complex nested relationships with dimensions/domains/categories
  - New model uses denormalized structure for better query performance
- **`OnlineAnswer`**: Replaced by `SubmissionStatus` model
  - Was an intermediate solution between `Question` and `SubmissionStatus`
  - `SubmissionStatus` provides better state tracking and error handling
- **`Answer`**: Legacy model replaced by domain-specific scoring in services
  - Old answer storage mechanism no longer used
  - Scoring now handled by `PaperEvaluationScoreService` and related services
- **`CustomField`**: Replaced by `EvaluationCustomField` with improved organization linkage
  - Old model had limited quiz-only scope
  - New model supports organization-wide custom fields

## Integration Points

### OCR Processing Pipeline
- Docker container processes PDF forms to detect bubble selections
- Python scripts in `docker/` handle image conversion (`pdf_to_image_converter.py`) and bubble detection (`bubble_detector.py`)
- Folio detection determines unique evaluation identifiers (4-digit format)
- Results saved as JSON files by folio in `docker/output/` for Laravel import
- Main entry point: `docker/main.py` orchestrates the full pipeline

### External Services & Real-Time Features
- Configured in `config/services.php` for mail, storage, and third-party APIs
- Laravel Reverb for real-time features (see README for deployment: https://codecourse.com/articles/deploying-reverb-on-laravel-forge)
- Laravel Telescope included for debugging and monitoring in development

### Cross-Component Communication
- Inertia.js bridges Laravel backend with Vue frontend (no API layer needed)
- Ziggy provides named route access in JavaScript (`route()` helper)
- Quiz data flows: Config files (`config/`) → Backend Models → Controller → Inertia Props → Vue Components
- OCR results: Docker JSON → Laravel Evaluation/PaperEvaluation JSON → Frontend display
- Public routes: `/evaluacion` for anonymous quiz access, authenticated routes under `/organization/{id}/*`

## Key Files and Directories

### Core Application Structure
- `app/Models/`: Domain models (Quiz, Evaluation, Organization, Folio, etc.)
- `routes/web.php`: Web routes including public evaluation access
- `database/migrations/`: Schema for psychological evaluation data
- `resources/js/Pages/`: Inertia.js pages for different application sections
- `resources/js/Components/Quiz/`: Reusable Vue components for quiz functionality

### Domain Configuration
- **NOM-035 Validated Instruments**:
  - `config/guide_i_questions.php`: Guide I - PTSD assessment questions in Spanish (acontecimientos traumáticos severos)
  - `config/escala_cisneros.php`: Workplace mobbing scale questions (violencia laboral/acoso psicológico)
  - `config/referencia_v.php`: Mexican demographic data structures aligned with NOM-035 requirements
  - `config/referencia_iii.php`: Reference III - Workplace psychosocial factors evaluation
  - `config/referencia_iii_reduced.php`: Reduced version for smaller organizations
- **Clima Laboral Configuration**:
  - `config/likert-value.php`: Likert scale scoring, interpretation levels, and dimension mapping
  - Defines 5 levels: Muy desfavorable, Desfavorable, Medio, Favorable, Muy favorable
- **Shared Configuration**:
  - `config/answer_values.php`: Standardized response options for NOM-035 scales
  - `config/question_dimensions.php`: Dimension/domain/category mappings for NOM-035 questions

### OCR Processing
- `docker/main.py`: Main OCR processing script for bubble detection
- `docker/bubble_detector.py`: Core bubble detection logic
- `docker/config.py`: OCR configuration for different form layouts

## Notes for AI Agents

### Domain Understanding
- This is a **NOM-035-STPS-2018 compliance platform** for Mexican workplace psychosocial risk assessment
- **Legal Framework**: Official Mexican standard requiring identification, analysis, and prevention of workplace psychosocial risk factors
- **Regulatory Requirements**: Different compliance levels based on organization size (≤15, 16-50, >50 workers)
- **Validated Instruments**: Uses officially validated questionnaires (Guide I, Escala Cisneros, Reference III/V) per NOM-035 specifications
- **Risk Factor Categories**: Covers trabajo-familia interference, violencia laboral, liderazgo negativo, cargas de trabajo, and entorno organizacional
- Data privacy is critical - UUIDs used for sensitive evaluation records
- Support both digital (online) and paper-based (OCR) evaluation workflows per regulatory flexibility

### Development Patterns
- Follow Laravel 11 conventions with modern patterns (`casts()` method, constructor promotion)
- Vue 3 Composition API with `v-model` for form components
- JSON storage pattern for flexible questionnaire data structures
- Modular component architecture in `Components/Quiz/` for reusability

### Critical Workflows
- **Multi-Instrument Assessment Cycle**: 
  - NOM-035: Biannual evaluations with three intervention levels (organizational, group, individual)
  - Clima Laboral: Work climate assessment with 5-level scoring system
  - Both can be combined in single paper form or separate online evaluations
- **Dual Processing Paths**:
  - **Online**: Quiz → `ProcessQuizSubmission` job → `SubmissionStatus` records
  - **Paper**: PDF → OCR (`docker/main.py`) → JSON → `ProcessPaperEvaluation` job → `PaperEvaluation` record
- **OCR Multi-Instrument Detection**: Single paper form can contain NOM-035 + Clima Laboral sections
  - Detected via `evaluation_type_code` in raw_data
  - Stored in separate JSON fields: `referencia_i_answers`, `referencia_iii_answers`, `likert_answers`
- **Report Generation**: 
  - NOM-035: Domain/dimension/category analysis with risk level classification
  - Clima Laboral: Likert scoring with distribution charts and level interpretation
  - Both use cached data from `OrganizationReportCacheService`

For domain-specific examples, consult configuration files in `config/` and the Quiz component documentation in `resources/js/Components/Quiz/README.md`.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.26
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/framework (LARAVEL) - v11
- laravel/nightwatch (NIGHTWATCH) - v1
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- laravel/telescope (TELESCOPE) - v5
- tightenco/ziggy (ZIGGY) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/vue3 (INERTIA) - v2
- vue (VUE) - v3
- laravel-echo (ECHO) - v1
- tailwindcss (TAILWINDCSS) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== herd rules ===

## Laravel Herd

- The application is served by Laravel Herd and will be available at: https?://[kebab-case-project-dir].test. Use the `get-absolute-url` tool to generate URLs for the user to ensure valid URLs.
- You must not run any commands to make the site available via HTTP(s). It is _always_ available through Laravel Herd.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- Build forms using the `useForm` helper. Use the code examples and `search-docs` tool with a query of `useForm helper` for guidance.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v11 rules ===

## Laravel 11

- Use the `search-docs` tool to get version specific documentation.
- Laravel 11 brought a new streamlined file structure which this project now uses.

### Laravel 11 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

### New Artisan Commands
- List Artisan commands using Boost's MCP tool, if available. New commands available in Laravel 11:
    - `php artisan make:enum`
    - `php artisan make:class `
    - `php artisan make:interface `


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== inertia-vue/core rules ===

## Inertia + Vue

- Vue components must have a single root element.
- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="vue">

    import { Link } from '@inertiajs/vue3'
    <Link href="/">Home</Link>

</code-snippet>


=== inertia-vue/v2/forms rules ===

## Inertia + Vue Forms

<code-snippet name="Inertia Vue useForm example" lang="vue">

<script setup>
    import { useForm } from '@inertiajs/vue3'

    const form = useForm({
        email: null,
        password: null,
        remember: false,
    })
</script>

<template>
    <form @submit.prevent="form.post('/login')">
        <!-- email -->
        <input type="text" v-model="form.email">
        <div v-if="form.errors.email">{{ form.errors.email }}</div>
        <!-- password -->
        <input type="password" v-model="form.password">
        <div v-if="form.errors.password">{{ form.errors.password }}</div>
        <!-- remember me -->
        <input type="checkbox" v-model="form.remember"> Remember Me
        <!-- submit -->
        <button type="submit" :disabled="form.processing">Login</button>
    </form>
</template>

</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v3 rules ===

## Tailwind 3

- Always use Tailwind CSS v3 - verify you're using only classes supported by this version.
</laravel-boost-guidelines>
