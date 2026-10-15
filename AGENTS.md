# Repository Guidelines

## Project Structure & Module Organization

This is a Laravel 10 application for practicing a post app. Application code lives in `app/`: models, controllers, policies, middleware, and Fortify actions. HTTP routes are in `routes/`; Blade templates and frontend assets are in `resources/views/`, `resources/js/`, and `resources/css/`. Database migrations, factories, and seeders are under `database/`. PHPUnit tests are in `tests/Feature/` and `tests/Unit/`. Put design notes and diagrams in `docs/`; tutorial answer materials are kept separately in `answers/` and do not affect the app.

## Build, Test, and Development Commands

Run commands from the repository root with Docker available:

- `./vendor/bin/sail up -d` starts the app and its services.
- `./vendor/bin/sail artisan migrate --seed` creates tables and loads practice data.
- `./vendor/bin/sail artisan test` runs the PHPUnit suite.
- `./vendor/bin/sail npm run dev` starts Vite for frontend development.
- `./vendor/bin/sail npm run build` builds frontend assets for production.
- `./vendor/bin/sail down` stops the containers.

For initial setup, follow the installation steps in `README.md` before running Sail commands.

## Coding Style & Naming Conventions

Follow Laravel and PSR-12 conventions for PHP: four spaces, StudlyCase classes (`PostController`), camelCase methods and variables, and descriptive migration names with timestamps. Keep routes RESTful and views organized by feature, such as `resources/views/posts/`. Use Laravel Pint (`./vendor/bin/sail pint`) to format PHP when needed. Keep documentation in Markdown and use clear, task-specific filenames, such as `expense-api.md`.

## Testing Guidelines

The project uses PHPUnit through Laravel's test runner. Add feature tests for user-visible behavior and authorization, and unit tests for isolated logic. Name test files with the `*Test.php` suffix and test methods descriptively (Laravel's `test_...` style is also suitable). Run the full suite with `./vendor/bin/sail artisan test`.

## Commit & Pull Request Guidelines

Recent commits use concise descriptions, often prefixed with a tutorial section (for example, `13-8: ...`); use the same pattern when a change belongs to a tutorial, or a short imperative summary otherwise. Keep commits focused. Pull requests should explain the behavior or documentation change, mention related tutorial sections or issues, and include screenshots for visible UI changes. Note the tests or checks run.

## Configuration & Safety

Keep local credentials in `.env`; do not commit secrets or real user data. Use the seeded dummy accounts for local practice. Review migrations and seeders for unintended destructive changes before submitting them.

