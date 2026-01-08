# Repository Guidelines

## Project Structure & Module Organization
- `app/` contains application code (controllers, models, jobs, etc.).
- `routes/` defines HTTP routes (`web.php`, `api.php`).
- `resources/` holds Blade views, frontend entrypoints, and assets compiled by Vite.
- `public/` is the web root for built assets and the front controller.
- `database/` contains migrations, factories, and seeders.
- `tests/Unit` and `tests/Feature` contain PHPUnit tests.

## Build, Test, and Development Commands
- `composer run setup` initializes the app (`.env`, key, migrate), installs Node deps, and builds assets.
- `composer run dev` starts a full local dev stack (PHP server, queue worker, logs, and Vite).
- `npm run dev` runs Vite in watch mode for frontend changes.
- `npm run build` creates production assets in `public/`.
- `composer run test` clears config cache and runs the Laravel test runner.

## Coding Style & Naming Conventions
- PHP follows PSR-12/Laravel conventions: 4-space indentation, StudlyCase classes, camelCase methods.
- Use `vendor/bin/pint` for PHP formatting (Laravel Pint).
- Keep file/class names aligned with PSR-4 namespaces under `app/`.
- Use descriptive migration names (e.g., `2024_01_01_000000_create_posts_table.php`).

## Testing Guidelines
- PHPUnit is configured via `phpunit.xml`; tests live under `tests/Unit` and `tests/Feature`.
- Name tests by behavior (e.g., `UserCanUpdateProfileTest`).
- Run tests with `composer run test` or `php artisan test`.

## Commit & Pull Request Guidelines
- No formal commit convention is established; use concise, imperative messages (e.g., “Add profile update flow”).
- PRs should include: a short summary, key implementation notes, and screenshots for UI changes.
- Link related issues/tickets when applicable and note any migrations or config changes.

## Security & Configuration Tips
- Never commit secrets; copy `.env.example` to `.env` for local setup.
- For local DB in tests, Laravel uses in-memory SQLite by default (`phpunit.xml`).
