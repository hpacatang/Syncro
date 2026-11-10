<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Submission content, review, and publishing

Submissions support two types: `social_media` posts (up to 2,200 characters) and `website_article` drafts (a headline and 100–12,000-character body). Existing clients that omit `submission_type` continue to create social-media submissions.

Reviewers can edit drafts manually or request an AI-generated draft. AI output is not saved or sent to submitters until a reviewer edits or confirms it with **Save & Send for Review**. Reviewers remain responsible for checking facts; prompts direct providers to preserve source facts and not invent details. Missing or expired provider keys leave manual review available.

Configure provider credentials in the server environment using `OPEN_AI_KEY`, `GEMINI_KEY`, or `DEEPSEEK_KEY`. The defaults are `gpt-5.4-mini`, `gemini-3.8-flash`, and `deepseek-flash`; optional overrides are `OPENAI_MODEL`, `GEMINI_MODEL`, and `DEEPSEEK_MODEL`. After changing environment values on a server with cached configuration, run `php artisan config:clear` and rebuild the config cache as part of deployment. Keep credentials server-side; do not put them in browser code.

The submitter's draft generator also supports local Ollama. With Ollama running on the same host as Laravel and `qwen3:8b` pulled, add `OLLAMA_BASE_URL=http://127.0.0.1:11434`, `OLLAMA_MODEL=qwen3:8b`, and optionally `OLLAMA_TIMEOUT=300` to `.env`. Ollama's native `/api/chat` endpoint receives the same social-post or article-specific guardrails and returns JSON; the application still validates headline/body lengths and uses the normal manual fallback if Ollama is unavailable or returns invalid output. The Ollama request raises PHP's execution time limit for that draft request to the configured Ollama timeout plus 15 seconds (capped at five minutes); it does not change the default limit for other requests. If `set_time_limit` is disabled by the PHP configuration, Ollama drafting cannot extend the limit and the application logs an error and returns the manual fallback. Restart/reload Ollama after pulling the model and run `php artisan config:clear` if Laravel caches config. If Laravel is inside Docker, `127.0.0.1` points to the container; use a host-accessible Ollama address such as `http://host.docker.internal:11434` when supported by the Docker setup. For a hosted application, the web server must be able to reach the Ollama machine over a private, secured network—do not expose the Ollama API publicly.

Approved social posts can be published to a Facebook Page using the [Page Feed](https://developers.facebook.com/docs/graph-api/reference/page/feed/) and [Page Photos](https://developers.facebook.com/docs/graph-api/reference/page/photos/) endpoints. Configure `META_ACCESS_TOKEN` with a **Page access token**, `META_PAGE_ID` with the destination Page ID, and optionally `META_PAGE_NAME` and `META_GRAPH_API_VERSION` (default `v23.0`). The token must have Meta's Page publishing permission, including `pages_manage_posts`; generate it for a Page the application is authorized to publish to. A user token alone may not be sufficient. The reviewer can publish immediately or schedule a future post; scheduled posts are checked every minute by `php artisan schedule:run` (use `php artisan schedule:work` locally). Failed API attempts are retained for review/retry. Publishing failures do not mark a submission as posted. The integration supports text/link posts and a single publicly accessible image; video, document, and multi-image publishing are not enabled in this proof of concept. Verify the Facebook Page after ambiguous API responses before retrying.

The review and evaluation pages include channel previews for Facebook posts and website articles. They are representative previews, not exact platform renderings. Article publishing is intentionally disabled until the university CMS API is available. The manually configured Page token is server-side; this is not an OAuth account-connection flow. Do not commit, display, or log the token.

Run `php artisan ai:check-providers` to send each configured provider a short synthetic prompt and report HTTP status, latency, usage, rate-limit headers, and safe error codes. It does not display keys, generated text, or provider error messages. This verifies current model/key access, not account billing or quotas; check each provider dashboard for those. Model context windows are larger than this workflow's intentional generation caps (900 output tokens for social posts and 3,000 for website articles), so long-form article quality/output length can still be constrained by the application prompt and cap. See the [OpenAI GPT-5.4 Mini](https://developers.openai.com/api/docs/models/gpt-5.4-mini), [Gemini 3.8 Flash](https://ai.google.dev/gemini-api/docs/models/gemini-3.8-flash), and [DeepSeek API model documentation](https://api-docs.deepseek.com/quick_start/pricing) for current provider limits and availability.

## Local frontend assets and storage troubleshooting

Bootstrap CSS is served from `public/css`, while Bootstrap JavaScript, Bootstrap Icons, and Font Awesome are bundled locally. After checkout or changing frontend assets, run `npm ci` and `npm run build`; deployments must include the resulting `public/build` assets and manifest. The application views no longer require icon stylesheets from jsDelivr or cdnjs.

The staff dashboard and media gallery must not query Supabase Storage to check each object while rendering HTML. Media object reads happen only when the browser requests a media URL. A PHP fatal error in Guzzle's `CurlMultiHandler` indicates a stalled outbound HTTP request, not by itself a PostgreSQL failure; this app's S3-compatible Supabase Storage adapter also uses Guzzle. A read-only database check and storage-object check can be run separately with Laravel Tinker:

```powershell
php artisan tinker --execute="DB::select('select 1');"
php artisan tinker --execute="Storage::disk('supabase')->exists(DB::table('media')->whereNotNull('path')->value('path'));"
```

The storage check requires at least one media row with a path. Avoid logging or sharing `.env` values, authorization headers, signed URLs, or full exception request options when diagnosing connectivity.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
