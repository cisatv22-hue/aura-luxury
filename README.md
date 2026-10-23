# AURA Store

Laravel 13, Vue 3, Inertia and Filament 5 storefront for AURA. PHP 8.4+, MySQL 8, Node 22, and PHP extensions `intl`, `gd`, `exif`, `mbstring`, `pdo_mysql`, and `zip` are required. The gallery generates WebP derivatives when GD has WebP support; it falls back to JPEG or PNG derivatives when the local GD build lacks WebP. WebP source uploads still need a WebP-capable GD or Imagick driver. Filament needs `intl`.

## Local setup

1. Install PHP and Node dependencies: `composer install`, run `corepack enable`, then `pnpm install` (the project pins pnpm 12.14.0).
2. Copy `.env.example` to `.env`, set the MySQL connection, and run `php artisan key:generate`.
3. Run `php artisan migrate --seed`.
4. From the `aura-store` directory, validate the legacy catalog using the sibling files in the parent directory: `php artisan aura:import-legacy --source="../js/main.js" --assets="../aura-v2-assets/products" --dry-run`, then repeat without `--dry-run`. Imported products stay unpublished with zero stock until reviewed.
5. Create the first admin with `php artisan aura:make-admin admin@example.com`. Set a temporary password when prompted. Log in, configure 2FA in account security, and confirm it before opening `/admin`.
6. Run `pnpm dev` and `php artisan serve`. Start a queue worker with `php artisan queue:work` for order alerts, newsletter confirmations, and restock notices.

The local `.env` may use SQLite for a quick disposable developer database. Production and CI use MySQL 8. Supply a real mail transport and a persistent queue worker before enabling customer email flows. The production build includes the Inertia SSR bundle; keep `php artisan inertia:start-ssr` running under a process manager in production. Set `BACKUP_DISK` to an externally configured disk such as `s3` in production and `BACKUP_NOTIFICATION_EMAIL` to the operations inbox. The scheduler verifies database and media archives after daily backups at 02:00, cleans old backups at 03:00, and monitors the configured disk at 04:00, keeping 30 days of backup history.

PHP must provide GD with WebP support or Imagick for routine Media Library conversions. If existing local `.webp` gallery files have missing conversions because the PHP build lacks WebP support, install FFmpeg with `libwebp` and run `php artisan aura:recover-webp-conversions --dry-run` to preview, then `php artisan aura:recover-webp-conversions` to create the `thumb`, `card`, and `large` files without changing originals. Use `--media=ID` to recover one media record or `--ffmpeg=PATH` to select the executable. The command is a recovery tool; configure WebP-capable GD or Imagick for normal uploads.

Payments and shipping default to safe mock drivers: set `PAYMENT_DRIVER=mock` and `SHIPPING_DRIVER=mock`. The payment contract verifies provider webhooks before an order can become paid; the mock rejects callbacks and never charges. The shipping mock returns a sample quote and `MOCK-…` tracking value without creating a label. Mercado Pago and a carrier adapter can replace these drivers once provider accounts, credentials, rates, and shipping choice are available.

Customers can register at `/register`, verify their email, and use `/cuenta` to see linked orders, manage addresses, and sync favorites. Customers have no staff role and cannot access `/admin`.

## Checks

- `composer ci:check` runs formatting, Larastan, Vue type checks, and Pest.
- `pnpm build` compiles storefront assets and the Inertia SSR bundle.
- CI runs Playwright on desktop/mobile, axe checks on public pages and Lighthouse mobile against the public pages.
- `composer audit --locked` checks PHP dependencies.

GitHub Actions installs PHP with `intl`, MySQL 8, Node 22, and pnpm 12.14.0; it runs the same CI checks plus the production asset build.

## Panel and permissions

The staff panel is at `/admin`. Staff accounts need one of the seeded roles (`admin`, `catalog`, `inventory`, or `orders`) and confirmed 2FA. `php artisan aura:make-admin EMAIL` creates the first administrator; use the Users and Roles pages for subsequent access management.

Products, colors and their reorderable multi-photo galleries, variants, categories, collections, looks with product hotspots, bundles, promotions, customers, inventory, shipping, orders, home content, pages, announcements, newsletter, and storefront settings are managed from the panel. Each gallery photo requires alternative text; Media Library generates its WebP conversions in the background. Inventory CSV files use `sku,stock`; each imported change creates a stock movement. Set the order notification email in Store Settings and configure a mail provider before launch. The legacy catalog importer creates drafts with zero stock; review products, photos, descriptions and real stock before publishing them.

The checkout asks for a postal code and shows a mock shipping estimate. The server recalculates the quote when it records the order and saves the postal code for the team. Replace the mock `ShippingProvider` implementation when a carrier or aggregator is selected.

The scheduler emails the configured order inbox once when a WhatsApp order remains in `Nuevo` for more than 48 hours. Set the order notification email in Store Settings and configure a persistent queue worker to deliver it.

Product pages show the full photo gallery for the selected color with an accessible zoom viewer. The size guide includes a height/weight calculator when its JSON rows contain AURA-approved `_size`, `_height_min_cm`, `_height_max_cm`, `_weight_min_kg`, and `_weight_max_kg` values; without those approved ranges, it explains that a recommendation is unavailable.

Items removed from the bag can be restored for five seconds with the Undo action.

The implementation status for the planned phases is tracked in [`docs/aura-v2/ESTADO-DE-IMPLEMENTACION.md`](docs/aura-v2/ESTADO-DE-IMPLEMENTACION.md).

At cutover, the legacy `/catalogo.html` URL permanently redirects to `/tienda`.

`E2ESeeder` creates published demo products for browser tests and refuses to run outside `local` or `testing`. Do not copy its data into staging or production.

## Deployment requirements

Configure `APP_ENV=production`, `APP_DEBUG=false`, MySQL credentials, HTTPS `APP_URL`, public storage (`php artisan storage:link`), SMTP credentials, a queue worker, and GD WebP support or Imagick. Set `BACKUP_DISK=s3` and its AWS-compatible credentials before launch. Install the Laravel scheduler as a cron task; it backs up the database and uploaded media daily and retains 30 days. Choose hosting, domain, shipping rates and the final store policies before launch.
