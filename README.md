# Capell starter kit

A standalone Laravel 13 application with Capell Core, Admin and Frontend pinned to exact released versions in `composer.json` and `composer.lock`, installed from Packagist. PHP 8.4+, Composer and SQLite are required. No Node build or queue worker is needed to try the site.

```sh
laravel new my-studio --using=capell-app/starter-kit
cd my-studio
php artisan serve
```

Open <http://localhost:8000> for the studio and <http://localhost:8000/admin> for Admin. Installation creates `admin@example.test` with a random password saved to `storage/app/private/starter-admin-password` (owner-readable only). Store the password securely and delete that file. Credentials, `.env` and SQLite data are never included in the repository or distribution.

The default mail driver is `log`: contact messages appear in `storage/logs/laravel.log`. Configure a real mail transport and change **Enquiry recipient** in the Admin dashboard before accepting real enquiries.

For a Git checkout:

```sh
composer install
composer run setup
php artisan serve
```

Setup creates missing schema and examples. Repeating it preserves the application key, administrator credentials, edited content, page names and withdrawn routes. It never runs `migrate:fresh`.

## Examples to copy

| Task | Example |
| --- | --- |
| App-local theme | `resources/views/capell/themes/capell-app/studio/theme.json`, its Blade layout and `public/css/starter.css` |
| Custom page type | `case-study` blueprint in `SeedStarterSiteAction`, with `CaseStudyPageConfigurator` providing client/year fields |
| Settings widget | `StudioSettingsWidget` → `StudioSettingsData` → authorised `SaveStudioSettingsAction` |
| Contact form | `ContactRequest` → `ContactData` → `SendContactMessageAction` → `ContactMessage` |
| Public hydration | `StudioRenderDataContributor`, registered using `PublicRenderDataContributor::TAG` |
| Content seeding | `SeedStarterSiteAction`, using durable example UUIDs and initial-only URL creation |

The custom page is available at `/work/field-notes`; edit it under **Pages** in Admin. The dashboard widget controls the studio name and enquiry recipient. The contact form validates input, keeps old values on validation errors, rate limits requests and sends synchronously.

Public Blade consumes hydrated values rather than querying models. The local layout/master views are registered with `LayoutViewRegistry`. The page configurator is contributed explicitly through `CapellAdmin::contributeToAdminSurface()`; `discoverConfigurators()` does not register app configurators in the pinned release.

The stylesheet is authored source served directly from `public/css/starter.css`. Admin uses Filament's published assets. HTML/render-data caching is disabled in `.env.example` so contact CSRF tokens, validation errors and flash confirmations remain request-local. Enable caching only after separating personalised form responses from shared HTML.

## Tests and releases

```sh
vendor/bin/pest --ci
composer run lint
bash -n scripts/deploy.sh
```

Pest exercises public CMS routes, escaping, Admin access and custom-field editing, settings authorisation and validation, contact delivery/validation/rate limits, and repeated setup. Test databases use isolated SQLite connections; no external database is required.

Generated dependency migrations are excluded from source formatting; `composer run lint` checks the authored migration separately.

Ordinary CI installs the committed lockfile, runs all examples and creates a fresh Composer project from the exact Git revision. The daily **Released Capell canary** also runs on `repository_dispatch` with event type `capell-release` and manual dispatch. It discovers the latest stable Packagist releases, writes temporary exact pins, resolves a lockfile and runs Pest. Dependabot checks daily and groups Capell pin/lock updates into one PR, which runs the ordinary fresh-project CI. This works with the repository’s read-only default Actions token; workflows do not need permission to approve or create their own PRs. The daily schedule is a fallback when a release publisher does not send the dispatch; this repository cannot configure another repository's release hooks.

To maintain pins locally:

```sh
php scripts/pin-capell-release.php --write
composer update --with-all-dependencies
composer run setup
vendor/bin/pest --ci
```

The public Composer package is `capell-app/starter-kit`. GitHub pushes must trigger its Packagist auto-update integration. Packagist registration is required for the short `--using` command; a GitHub URL is not a substitute for that acceptance check.

## Deployment

Point the web root at `public/`. Use HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, a unique persisted `APP_KEY`, a real mail transport, a private persistent database and writable persistent `storage/`. Back up the database before migrations; run the script in a prepared release directory and switch the serving symlink only after your own HTTP health checks pass.

On the first production bootstrap, copy `.env.example` to `.env`, configure it, then run:

```sh
composer install --no-dev --prefer-dist --no-interaction
php artisan starter:install --admin-email=you@your-domain.example --no-interaction
```

Production bootstrap refuses the local example administrator address. Retrieve and remove the private password file, sign in, set the enquiry recipient and replace the example content. The installer uses the released migration publishers without rewriting application source or running nested Composer operations.

For subsequent releases:

```sh
bash scripts/deploy.sh
```

The script publishes migrations from locked dependencies, applies schema/settings migrations, publishes Admin assets, rebuilds package/config/route/view caches and restarts queue workers. It does not reseed content, rotate the key, reset data, switch symlinks or manage backups. Keep production database/storage paths outside disposable release directories.
