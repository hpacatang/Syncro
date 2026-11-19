# Syncro contributor guidance

## What this repository is

Syncro is a Laravel 12 application for academic submission intake, review, and publication workflows. Read `README.md` for local setup, integration configuration, test commands, and the project map.

## Work carefully

- Inspect the existing implementation and nearby tests before changing behavior. Prefer the repository's existing Laravel patterns and keep changes scoped.
- Enforce authorization in routes, policies, middleware, and queries. Hiding a UI action is not authorization. Preserve submitter ownership and department-review boundaries.
- Treat the `departments` table as school/department metadata. A department reviewer is a `users` record with role `department`.
- `workflow_status` is the submission lifecycle source of truth; retain compatibility with the legacy `status` column and its existing callers.
- `review_department_id = null` routes to PAIR. A non-null value routes to that department.
- Persist timestamps in UTC and use `App\Support\AppTime` for user-facing local date parsing/formatting. The default display zone is `Asia/Manila`.
- Keep secrets out of source, tests, logs, and responses. Do not make live Meta/AI calls during tests or expose storage/AI endpoints unnecessarily.
- For external integrations, use Laravel HTTP fakes; use `Queue::fake()`/`Bus::fake()` and `Storage::fake()` where appropriate. Tests must not depend on network services or developer credentials.
- Add focused tests for behavior changed, especially authorization, workflow transitions, optional fields, and error/retry cases. Update docs when an operational or user-facing behavior changes.
- Do not use `migrate:fresh` against any database that contains data to preserve. Use `RefreshDatabase` for isolated test data and forward-only migrations for schema changes.
- Avoid unrelated refactors. Do not silently swallow errors or return success-shaped fallbacks when an operation fails.

## Validation commands

Run the relevant test file or case first, then the full suite when practical:

```sh
php artisan test tests/Feature/RelevantTest.php
php artisan test --filter=relevant_behavior
php artisan test
```

Additional checks:

```sh
php artisan view:cache
vendor/bin/pint --test
npm run build
git diff --check
```

Only run checks relevant to the change. Report existing unrelated failures separately rather than hiding or broadening the change to address them.

## Code map

- `routes/`: web, API, and scheduler routes.
- `app/Http/Controllers/`: request handling; keep domain logic in existing services when appropriate.
- `app/Policies/` and `app/Http/Middleware/`: access control.
- `app/Services/`, `app/Jobs/`, and `app/Support/`: workflows, external integrations, background work, and shared helpers.
- `app/Models/`: Eloquent models and relationships.
- `database/migrations/`, `database/factories/`, and `database/seeders/`: schema, test data, and local demo data.
- `resources/views/`: Blade pages and components.
- `tests/Feature/` and `tests/Unit/`: application workflows and isolated logic.