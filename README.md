<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

-   [Simple, fast routing engine](https://laravel.com/docs/routing).
-   [Powerful dependency injection container](https://laravel.com/docs/container).
-   Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
-   Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
-   Database agnostic [schema migrations](https://laravel.com/docs/migrations).
-   [Robust background job processing](https://laravel.com/docs/queues).
-   [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

-   **[Vehikl](https://vehikl.com)**
-   **[Tighten Co.](https://tighten.co)**
-   **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
-   **[64 Robots](https://64robots.com)**
-   **[Curotec](https://www.curotec.com/services/technologies/laravel)**
-   **[DevSquad](https://devsquad.com/hire-laravel-developers)**
-   **[Redberry](https://redberry.international/laravel-development)**
-   **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

I'll outline a complete, actionable implementation plan for a Laravel app that scrapes casting listings in Italy. I'll cover architecture, concrete files to add, data model, scraping strategy (adapters/parsers), scheduling/queues, storage, deduplication, testing, CI, deployment, and legal/ethical notes. After the plan I'll give a prioritized implementation roadmap and estimated effort.

What I'm doing next
Design a full, practical implementation you can use to build CastScraper end-to-end: code structure, models, jobs, scraping adapters, tests, and deploy notes — with explicit filenames and patterns you can start implementing immediately.

High-level architecture (why and components)
Single Laravel app (existing skeleton) acts as:
Orchestrator and API (web + artisan CLI)
Persistent storage (MySQL/SQLite/Postgres)
Background worker host (Laravel queues; queue:listen used in dev)
Scheduler (Laravel scheduler via cron / artisan schedule:run)
Scrapers implemented as modular "site adapters" (one adapter per source) that produce normalized Listing objects.
Fetching layer: HTTP client with polite defaults, retries, and rate-limiting. Use Guzzle + Symfony DomCrawler/CSS selectors for HTML parsing.
Headless fallback: for JS-heavy sites, run a small Node service (Puppeteer) or use php-headless-chrome wrappers. Keep this optional to reduce complexity.
Queue & concurrency: Jobs push per-source scraping tasks to queue; workers run them. Use database driver initially (works out-of-the-box). For scale, switch to Redis.
Deduplication & canonicalization: normalize title, company, date, and URL; compute a content hash to avoid duplicates.
Storage: normalized listings table, sources table (site config), attachments table (images/pdf), and scrape_runs table for audit/metrics.
Admin & API: routes to list sources, view listings, trigger scrape manually, and export CSV/JSON.
Concrete DB schema (tables and purpose)
Suggested tables (create migrations under migrations):

sources — configuration for each site
id, name, base_url, list_url, adapter_class, rate_limit_per_minute, active(boolean), last_scraped_at, selectors(json) optional
listings — normalized scraped casting posts
id, source_id, external_id (site-specific id), title, description (text), company, location, city, region, country (default 'Italy'), date_posted (nullable), valid_until (nullable), url, content_hash, canonical (boolean), scraped_at, parsed_at, raw_html (nullable), extra json
unique index on (source_id, external_id) and/or content_hash
attachments — images/files attached to a listing
id, listing_id, source_url, local_path, mime, size
scrape_runs — health/metrics for each scraping run
id, source_id, started_at, finished_at, status, items_found, items_saved, errors_count, meta (json)
failed_jobs — you get this from Laravel queue failed jobs table (artisan make:migration exists)
optional companies, locations normalized tables if you want relational lookups.
Examples: migrations named 2026_01_04_000001_create_sources_table.php, etc.

File layout and concrete artifacts to add
Create these files (suggested paths and brief purpose):

app/Scrapers/Fetcher.php
Wrapper around Guzzle with polite headers, timeout, exponential backoff, and optional proxy support.
app/Scrapers/AdapterInterface.php
contract: public function parseList(string $html, Source $source): array (returns array of item arrays or Listing DTO)
app/Scrapers/Adapters/<SiteName>Adapter.php
Site-specific extraction using DomCrawler (implements AdapterInterface).
app/Scrapers/Parser.php
Common parsing helpers (normalize dates, locations, strip boilerplate).
app/Jobs/ScrapeSourceJob.php
Job to scrape a Source (pagination handling, enqueuing per-page jobs if needed).
app/Jobs/ProcessListingJob.php
Job to normalize and persist single listing data; handles dedupe and attachments download.
app/Models/Source.php, Listing.php, Attachment.php, ScrapeRun.php
Eloquent models + relationships.
database/migrations/\*_\*\*\_create_sources_table.php etc.
web.php additions:
Admin routes: GET /admin/sources, POST /admin/sources/{id}/scrape (auth gated; for skeleton dev can be local-only)
app/Console/Commands/ScrapeCommand.php
Artisan command to trigger scrapes: php artisan scrape:source {source} {--force}
resources/views/admin/_ (optional simple Blade views for listing sources + last runs)
tests/Feature/ScraperTest.php and tests/Unit/\* parsers
docs/SCRAPING_POLICY.md — records robots.txt behaviors, rate limits, and contact info for takedowns.
Scraping strategy (fetch -> parse -> persist)
Fetch list pages using Fetcher with a per-source rate limit.
Parse list page via the source Adapter to extract item URLs & basic fields (external_id, title, date, snippet).
Enqueue per-item ProcessListingJob to fetch detail page if needed.
On ProcessListingJob:
Fetch detail page
Parse detail via adapter
Normalize fields, compute content_hash (e.g., sha1 of normalized title+company+date+maintext)
Check dedupe: if existing content_hash or (source_id + external_id) exists, update timestamps and skip insert
Save attachments (images) to storage (use storage disk local or public) and record in attachments
Log errors to scrape_runs and Sentry/monitoring if available
Mark run metrics in scrape_runs.
Parsing choices:

Use Symfony DomCrawler + CSS selectors; selectors stored in sources.selectors JSON for quick site rule changes without code edits for simple sites.
For more complex tasks (JS-heavy), use an external Puppeteer microservice. Prefer keeping site adapters in PHP where possible.
Rate limiting, politeness, and proxies:

Respect robots.txt. Have Fetcher check robots.txt for disallowed paths before scraping a source.
Default rate: 1 request/sec per source (configurable per source)
Use backoff on 429/5xx (exponential backoff).
Optional: rotate proxies for sites with aggressive blocking; track IP bans.
Deduplication & canonicalization:

Compute a content hash (sha1 of normalized content).
Use (source_id, external_id) if the site supplies stable ids.
On duplicates, update listings.scraped_at but avoid re-downloading attachments.
Attachments:

Prefer to store attachments on disk with hashed filenames and record paths in DB. For scale use S3 (Laravel filesystem).
Sanitize file types and max size.
Queues & scheduling

Use Laravel queues. In dev, database driver is fine; for production use Redis.
Use php artisan queue:work --tries=3 in supervisor/pm2 to run workers.
Scheduler: add app/Console/Kernel.php entries to schedule scrapes per-source: e.g., daily or hourly depending on source.
Add cron entry: \* \* \* \* \* cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
Monitoring & observability

Track metrics: items found, items saved, error rates, avg time per page.
Log exceptions to Sentry or external monitoring.
Emit basic Prometheus metrics (optional) via a small exporter.
Testing

Unit tests for each adapter's parse methods using sample HTML saved under tests/fixtures/.
Feature tests for jobs using in-memory sqlite (phpunit.xml already configured).
CI: GitHub Actions workflow to run composer install and composer run-script test on PRs.
Security & legal (must-read)

Adhere to robots.txt and site Terms of Service. Provide a way to opt-out/remove content and a contact email in README.
Rate-limit and avoid high-frequency scraping that looks like a DDoS.
Sanitize HTML before storing (strip scripts), and avoid executing any third-party JS on server.
Performance & scaling

For small scale: DB queue + single worker is sufficient.
For moderate scale: Redis queue, multiple workers, S3 for attachments, and separate headless service for JS rendering.
Use caching (Redis) for robots.txt and page-level caching for sites polled frequently.
CI/CD & deployment

Add a GitHub Actions workflow that runs tests on PR.
For production: deploy to a Linux VM with Supervisor managing php artisan queue:work and cron entry for schedule, or use Forge/Envoyer or platform like Laravel Vapor (if serverless).
Use environment variables in .env for DB, queue driver, S3 creds, proxy list, and monitoring DSN.
Concrete examples & snippets (non-executable here; implement in project)

Fetcher (concept):
Guzzle client with headers: User-Agent, Accept-Language: it-IT, timeout 15s, retry middleware for 429/5xx.
Adapter:
use Symfony\Component\DomCrawler\Crawler;
$crawler = new Crawler($html); $crawler->filter('.listing')->each(...);
ProcessListingJob pseudocode:
fetch detail page -> parse -> normalize -> compute hash -> DB transaction to insert listing + attachments -> emit metrics
Suggested concrete filenames to implement first (priority order)

database/migrations/_\_create_sources_table.php
database/migrations/_\_create_listings_table.php
app/Models/Source.php, Listing.php, Attachment.php
app/Scrapers/Fetcher.php, AdapterInterface.php, Parser.php
app/Scrapers/Adapters/ExampleSiteAdapter.php (one working adapter)
app/Jobs/ScrapeSourceJob.php, ProcessListingJob.php
app/Console/Commands/ScrapeCommand.php
Add schedule in app/Console/Kernel.php
tests/fixtures/example_site/list_page.html and tests/Unit/Adapters/ExampleSiteAdapterTest.php
docs/SCRAPING_POLICY.md and update README.md
Estimated effort (rough)

MVP (one adapter, DB, jobs, basic admin UI, tests): 3–6 days (single developer).
Full system (multiple adapters, headless fallback, S3, monitoring, CI): 2–3 weeks.
Acceptance criteria (what "done" looks like)

System can be configured with a Source for at least one site and successfully scrape and persist listings via a scheduled job or manual trigger.
Deduplication works: repeated runs do not create duplicates.
Tests cover parser and job logic.
Admin pages show last run and allow manual triggers.
Scraping respects robots.txt and configured rate limits.
Next steps I can take now

Generate migration stubs and model classes in the repo.
Implement a sample Adapter for one site (need a target site or sample HTML fixtures).
Add unit tests for the adapter with saved html fixtures.
Add an artisan command and schedule entry.
Which next action would you like me to take?
Options:

I can create the migrations and Eloquent models now.
I can scaffold the scrapers and a working example adapter (need a sample site or HTML).
I can add jobs and wire up the artisan command + schedule.
I can create tests and sample fixtures.
Tell me which piece to implement first and I'll start coding it.
