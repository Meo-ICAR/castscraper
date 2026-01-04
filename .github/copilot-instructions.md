# Copilot / AI agent instructions — CastScraper (Laravel skeleton)

This project is a Laravel 12 application skeleton with a small app built on the standard Laravel layout. The goal of this file is to give an AI coding agent the minimal, high-value facts to be immediately productive without guessing project-specific conventions.

Summary (big picture)

-   Laravel 12 PHP app (requires PHP ^8.2). Autoloading: PSR-4 `App\\` -> `app/`.
-   Entry points: `public/index.php`, CLI: `artisan`.
-   Routes: `routes/web.php` (basic; add new routes here). Controllers live in `app/Http/Controllers` and inherit from `app/Http/Controllers/Controller.php`.
-   Models: `app/Models` (example: `User.php`). Factories in `database/factories`.

Critical developer workflows (how to run/build/test)

-   Install & setup (project-local): run Composer then npm, then migrations.
    -   Preferred: `composer run-script setup` (runs composer install, copies .env, key:generate, migrate, npm install, npm run build)
    -   Dev environment (concurrent processes): `composer run-script dev` — this runs `php artisan serve`, `queue:listen`, `php artisan pail`, and `npm run dev` in parallel.
-   Run tests: `composer run-script test` which runs `php artisan config:clear && php artisan test`. PHPUnit config is `phpunit.xml` and tests use sqlite in-memory by default (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).

Project-specific conventions & patterns

-   Database & models
    -   Eloquent models are under `app/Models`. Models use PHP 8 attributes/comments for factory usage (see `User.php` and `database/factories/UserFactory.php`).
    -   Migrations live under `database/migrations` and a SQLite file may be used by scripts (see composer post-create hooks).
-   Background jobs & logs
    -   The `dev` composer script launches `php artisan queue:listen --tries=1` and `php artisan pail --timeout=0` (project uses `laravel/pail`) — when adding jobs, ensure they are compatible with the `queue:listen` environment used in development.
-   Frontend
    -   Vite + npm: frontend assets in `resources/js` and `resources/css`. Use `npm run dev` for hot reloading and `npm run build` for production assets.

Integration points and dependencies

-   Major dependencies (composer): `laravel/framework`, `laravel/tinker`, `laravel/boost`, `laravel/pail`, `laravel/pint`, `laravel/sail`.
-   Node: project expects `npm` and Vite; `vite.config.js` is present.
-   Tests rely on phpunit and Laravel's test runner (`artisan test`). Environment for tests is configured in `phpunit.xml`.

Helpful examples (copy-paste ready)

-   Add a simple GET route returning a view:
    -   `routes/web.php` shows the pattern: Route::get('/', fn() => view('welcome'));
-   Create a controller method and route:
    -   New controller: `app/Http/Controllers/MyController.php` -> class `MyController extends Controller` and declare `public function index()`; register with `Route::get('/my', [MyController::class, 'index']);` in `routes/web.php`.

Agent behavior guidance (what to do & avoid)

-   Prefer small, focused changes. Update tests when changing behavior.
-   Respect existing Laravel conventions (Service Providers under `app/Providers`, middleware registration in `bootstrap` / `app/Http/Kernel.php` if present).
-   When modifying DB schema, add a corresponding migration under `database/migrations` and, when possible, a small feature test that exercises the migration + model behavior.
-   Use composer scripts (listed in `composer.json`) rather than inventing ad-hoc process wiring — these scripts express intended developer workflows (setup, dev, test).

Files and locations you should reference often

-   Routes: `routes/web.php`
-   Main controllers: `app/Http/Controllers/Controller.php` and other files in that directory
-   Models: `app/Models/*.php`
-   Frontend: `resources/js/`, `resources/css/`, `vite.config.js`
-   Tests: `tests/Feature`, `tests/Unit`, `phpunit.xml`
-   Dev scripts: `composer.json` (scripts), `package.json` (npm scripts)

If you must make assumptions

-   Assume PHP 8.2 and Laravel 12 behavior. Tests run with sqlite in-memory unless a different DB is explicitly configured in CI.
-   Assume the repository root is project root and `composer` + `npm` are available on the machine.

When you're done editing

-   Run `composer run-script test` and `npm run build` locally to validate changes.

Questions for the human maintainer

-   Are there any custom service providers, middleware, or environment variables not in source control I should know about?
-   Is there a preferred branching or PR naming convention for this repo?

---

Note: No `.github/copilot-instructions.md` existed previously; this file was generated from discoverable project files (`composer.json`, `phpunit.xml`, `routes/web.php`, `app/Models/User.php`, `README.md`). If you'd like more examples (common refactoring patterns, code style rules, or conventions for migrations), tell me which area to expand.
