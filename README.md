# Syncro

Syncro is a Laravel application for an academic institution to collect, review, and manage student and department publication submissions. Submitters can route work to a department reviewer or directly to PAIR for university-wide publication. Reviewers evaluate social posts and website article drafts; approved social posts can be published to a configured Facebook Page.

## What the application does

- Accepts account requests and lets authorized staff approve or reject them.
- Supports student/organization (`org`) submitters, department reviewer accounts, PAIR reviewers, admins, and super admins.
- Routes submissions to a submitter's department or PAIR.
- Supports social media posts and website article drafts, with optional links and multiple media attachments.
- Tracks review and publication workflow, feedback, scheduling, and notifications.
- Offers optional AI draft generation through OpenAI, Gemini, DeepSeek, or a reachable Ollama service. Manual drafting remains available.
- Publishes approved social posts to a configured Facebook Page. Article publishing is not connected until the university CMS integration is available.

## Stack and requirements

- PHP 8.2 or newer and Composer
- Node.js and npm for frontend assets
- SQLite for a convenient local development/test database; PostgreSQL is used for the hosted Supabase database
- Laravel 12, Blade, Vite, Bootstrap, and Tailwind CSS

## Local setup

1. Install PHP/Composer and Node.js/npm, then install dependencies:

   ```sh
   composer install
   npm ci
   ```

2. Create the local environment file and application key:

   ```sh
   cp .env.example .env
   php artisan key:generate
   ```

   On PowerShell, use `Copy-Item .env.example .env` instead of `cp`.

3. Configure local SQLite and storage in `.env`. The example file selects SQLite and local public storage; no Supabase, Meta, or AI credentials are needed for ordinary local development. Create the SQLite file if it does not exist:

   ```sh
   php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
   ```

   PowerShell alternative:

   ```powershell
   if (-not (Test-Path database\database.sqlite)) { New-Item -ItemType File database\database.sqlite }
   ```

4. Run migrations and build the frontend:

   ```sh
   php artisan migrate
   npm run build
   ```

5. Start the app. In separate terminals, run:

   ```sh
   php artisan serve
   npm run dev
   php artisan queue:work --tries=1 --timeout=360
   php artisan schedule:work
   ```

   The queue worker is needed for queued AI draft generation. The scheduler checks due Facebook posts. If you only need to work on a page or synchronous feature, the app and Vite processes may be enough.

`composer run dev` is also available and starts the Laravel server, queue listener, log tail, and Vite together. It requires the Composer script's `npx concurrently` dependency.

### Optional demo data

Run `php artisan db:seed` only against a disposable local database. The seeder creates demo accounts and sample submissions; do not use those accounts or credentials in a shared or production environment. Tests should normally create only the records they need with model factories.

## Tests and checks

PHPUnit is configured to use an in-memory SQLite database, array cache/session, and synchronous queue. The test suite does not require access to the configured Supabase, Meta, or AI services; tests should fake HTTP, queue, and storage interactions.

Run the complete suite:

```sh
php artisan test
```

Run a focused test file or case while iterating:

```sh
php artisan test tests/Feature/DepartmentWorkflowTest.php
php artisan test --filter=department_reviewer_can_review
```

Useful focused suites:

| Area | Test file |
| --- | --- |
| Account requests and login | `tests/Feature/AccountRequestTest.php` |
| Roles, department routing, and reviewer boundaries | `tests/Feature/DepartmentWorkflowTest.php`, `tests/Feature/SubmissionAccessControlTest.php`, `tests/Feature/DefenseReadinessTest.php` |
| Submission types, AI drafting, and review | `tests/Feature/SubmissionContentEvaluationTest.php` |
| Upload validation, duplicate detection, scheduling, and notifications | `tests/Feature/SubmissionEnhancementsAndDuplicateCheckTest.php`, `tests/Feature/MediaNotificationOwnershipTest.php` |
| Facebook publishing behavior | `tests/Feature/MetaPublishingTest.php` |
| Display and storage time conversion | `tests/Feature/AppTimeTest.php` |
| Profile updates | `tests/Feature/ProfileManagementTest.php` |
| Lifecycle rules and media helpers | `tests/Unit/SubmissionLifecycleServiceTest.php`, `tests/Unit/SubmissionLifecycleStatusTest.php`, `tests/Unit/SubmissionMediaTest.php` |

Before submitting a change, run the narrowest relevant test(s), then the full suite when practical. For Blade changes, also run `php artisan view:cache`; for PHP formatting, use `vendor/bin/pint --test`; for frontend changes, run `npm run build`. Run `git diff --check` to catch whitespace errors.

If a test fails, reproduce it by itself and compare the failure to the branch's baseline before attributing it to your change. Avoid changing unrelated behavior just to make a focused test pass.

## Architecture map

| Location | Responsibility |
| --- | --- |
| `app/Http/Controllers/` | Web/API request handling |
| `app/Models/` | Eloquent models and relationships |
| `app/Policies/`, `app/Http/Middleware/` | Authorization and role/dept boundaries |
| `app/Services/` | Submission lifecycle, drafting, similarity checks, publishing, and audit behavior |
| `app/Jobs/` | Queued work, including AI draft generation |
| `app/Support/` | Shared time, media, notifications, and formatting helpers |
| `app/Console/Commands/` | Scheduled publishing and provider diagnostics |
| `routes/web.php`, `routes/api.php`, `routes/console.php` | Web, API, and scheduled routes |
| `database/migrations/`, `database/factories/`, `database/seeders/` | Schema evolution and test/demo data |
| `resources/views/` | Blade pages and reusable UI components |
| `tests/Feature/`, `tests/Unit/` | Workflow/integration and isolated behavior tests |

Submissions use `workflow_status` for their lifecycle; the legacy `status` column remains for compatibility with existing views/data. `review_department_id = null` means PAIR; otherwise the submission is routed to that department. A department is a `users` account with role `department`, linked to the `departments` identifier table through `department_id`.

## Optional integrations

Add credentials to the server-side `.env` only. Never commit secrets, paste them into tests, or include them in logs or screenshots.

### AI draft generation

- OpenAI: `OPEN_AI_KEY`; optional `OPENAI_MODEL` (defaults to `gpt-5.4-mini`)
- Gemini: `GEMINI_KEY`; optional `GEMINI_MODEL` (defaults to `gemini-3.8-flash`)
- DeepSeek: `DEEPSEEK_KEY`; optional `DEEPSEEK_MODEL` (defaults to `deepseek-flash`)
- Ollama: `OLLAMA_BASE_URL` (defaults to `http://127.0.0.1:11434`), `OLLAMA_MODEL` (defaults to `qwen3:8b`), and optional `OLLAMA_TIMEOUT` (defaults to 300 seconds)

The Ollama service must be reachable from the Laravel process. Draft generation is queued; run a worker and ensure the queue retry window exceeds the job timeout. If Laravel runs in a container, `127.0.0.1` refers to that container, not the host. Do not expose Ollama's API publicly.

AI-generated text is a draft, not a verified source. Reviewers are responsible for checking facts before approval.

Run `php artisan ai:check-providers` to check configured AI providers with short synthetic requests. The command reports safe diagnostic metadata, not generated text or credentials; it does not establish account quota or billing status.

### Facebook Page publishing

Configure `META_ACCESS_TOKEN` with a Page access token and `META_PAGE_ID` with the target Page ID. Optional settings are `META_PAGE_NAME` (defaults to `USJR`) and `META_GRAPH_API_VERSION` (defaults to `v23.0`). The token must have the Page publishing permission required by Meta, including `pages_manage_posts`.

Publishing supports text/link posts and one JPEG, PNG, GIF, BMP, or TIFF image up to 10 MB. Videos, documents, and multiple attachments are not currently published. Article publishing remains unavailable until a university CMS integration exists. Verify the Page after an ambiguous API response before retrying.

### Storage and uploads

The local example uses Laravel's `public` disk. For Supabase Storage, configure `FILESYSTEM_DISK=supabase` and the `SUPABASE_S3_KEY`, `SUPABASE_S3_SECRET`, `SUPABASE_S3_REGION`, `SUPABASE_BUCKET`, `SUPABASE_S3_ENDPOINT`, and `SUPABASE_PUBLIC_BASE_URL` values. Keep bucket credentials private.

Submissions accept up to 10 attachments, 50 MB each, and 100 MB total. The PHP upload limits and web server request-body limit must allow the intended upload size. `public/.user.ini` applies only to PHP-FPM/FastCGI configurations that honor per-directory overrides; local `php artisan serve` uses the CLI `php.ini` reported by `php --ini`.

## Contribution guide

1. Read [`AGENTS.md`](./AGENTS.md) for repository-specific engineering guidance.
2. Find the closest existing controller/service/view/test pattern before adding a new abstraction.
3. Keep changes focused, preserve role and department authorization at the server, and add/update tests for behavior changes.
4. Use fakes for external services and avoid network calls in automated tests.
5. Do not run `migrate:fresh` against any database with data you need. Migrations should be additive/reversible where practical and tested on SQLite and PostgreSQL-sensitive paths as applicable.
6. Describe user-visible behavior and any manual setup or verification required in your change summary.

## Additional references

- Laravel 12 documentation: https://laravel.com/docs/12.x
- Meta Graph API Pages documentation: https://developers.facebook.com/docs/pages-api/
- Ollama API documentation: https://github.com/ollama/ollama/blob/main/docs/api.md
