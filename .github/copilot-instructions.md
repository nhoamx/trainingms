# Copilot Instructions for `trainingms`

This document provides guidance for AI coding agents working on the `trainingms` project. It outlines the architecture, workflows, conventions, and integration points to help agents be productive immediately.

## Project Overview

`trainingms` is a Laravel-based application. It includes the following key components:

- **App Layer** (`app/`): Contains core application logic, including controllers, models, and services.
- **Database Layer** (`database/`): Includes migrations, seeders, and factories for managing database schema and test data.
- **Configuration** (`config/`): Centralized configuration files for services, caching, authentication, etc.
- **Public Assets** (`public/`): Contains public-facing assets like the `index.php` entry point and static files.
- **Resources** (`resources/`): Includes views, JavaScript, and CSS files.
- **Routes** (`routes/`): Defines application routes (`web.php`, `api.php`, etc.).

## Developer Workflows

### Database Migrations and Seeding
- Run migrations with `php artisan migrate`.
- Seed the database using `php artisan db:seed`. For specific seeders, use `--class`, e.g., `php artisan db:seed --class=RolesSeeder`.

### Testing
- Run tests with `php artisan test` or `vendor/bin/phpunit`.
- Use `php artisan test --filter=TestName` to run a specific test.
- Always use DatabaseTransactions for tests that modify the database.
- Feature tests are located in `tests/Feature/`.

## Project-Specific Conventions

- **Role Management**: Roles are seeded using the `RolesSeeder` class in `database/seeders/`. It uses `firstOrCreate` to avoid duplicate entries.
- **Configuration**: Sensitive data is managed via `.env` files. Avoid hardcoding sensitive values.
- **Frontend**: Tailwind CSS is configured via `tailwind.config.js`. Build assets using `npm run dev` or `npm run build`.

## Integration Points

- **External Services**: Configured in `config/services.php`. Examples include mail, storage, and third-party APIs.
- **Docker**: A `docker-compose.yml` file is available for containerized development. Key scripts are in the `docker/` directory.
- **Telescope**: Laravel Telescope is included for debugging and monitoring.

## Key Files and Directories

- `app/Models/`: Contains Eloquent models.
- `routes/web.php`: Defines web routes.
- `database/migrations/`: Manages database schema.
- `resources/views/`: Blade templates for the frontend.
- `config/`: Centralized configuration files.

## Notes for AI Agents

- Follow Laravel conventions for naming and structure.
- Use `php artisan` commands for common tasks.
- When adding new features, ensure they are covered by tests in `tests/`.
- Respect existing patterns, such as using service classes for business logic.

For further clarification, consult the Laravel documentation or ask for specific examples from the codebase.
