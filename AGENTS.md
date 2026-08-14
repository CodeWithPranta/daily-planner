# AGENTS.md — Daily Planner PWA

## Project location
`D:\laravel-apps\daily-planner` — Laravel 13 + Livewire 4 (Blaze) + Flux + Tailwind v4. SQLite dev DB.

## Tooling commands (run from the daily-planner directory)
- Install: `composer install` then `npm install`
- Dev server + Vite: `php artisan serve` and `npm run dev` (or `composer dev`)
- Build assets: `npm run build`
- Migrate & seed demo data: `php artisan migrate:fresh --seed`
  - Demo login: `pranta@example.com` / `password`
- Tests only: `php artisan test`
- The full canonical check (run before finishing a phase):
  `composer test`
  - It runs: lint (`pint --parallel --test`) + type check (`phpstan analyse`, level 7) + unit/feature tests.

## Code quality rules
- Keep Livewire components thin; put business logic in `app/Services` and `app/Actions`.
- Use PHP enums for statuses/priorities (`App\Enums\*`).
- Authorize every user-owned resource via policies in `app/Policies`.
- Add DB indexes / unique constraints where frequently queried; supply `(int) Auth::id()` when calling `forUser()` scopes (the type checker expects int).
- Annotate Eloquent relations and Attribute accessors with generics (`@return BelongsTo<User, $this>` etc.) so PHPStan passes.
- Do not commit generated placeholder files; do not add `@phpstan-ignore` lines.

## Phase progress
- Phase 1 (done): scaffold, auth, base mobile layout, bottom nav, dashboard shell.
- Phase 2 (done): categories, goals, daily plans, tasks; migrations/models/enums/policies/services; Livewire CRUD; demo seeder.
- Phase 3 (done): task progress updates, timer sessions, daily productivity score service, live dashboard, task detail page, timer bar, smoke tests.
- Phase 4 (done): end-of-day review, streaks, weekly/monthly reports, daily score caching, seed historical data, report and review Livewire components.
- Phase 5 (done): analytics page with Chart.js (score trend, category doughnut, weekly stacked bar), goal progress service (auto-sync from tasks), category insights, enhanced goals view with progress rings.
- Phase 6 (done): PWA manifest, service worker, offline page, app icons, theme-color, SW registration in app.js.
- All phases complete. 123 tests passing.