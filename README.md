# PixelForge

PixelForge is a web app for generating images from a text prompt. Users buy credits, see the price of an image before they send it, and pay what it actually cost, plus a markup. If an image fails, they pay nothing. Images are made by the [Pixelforge API](https://pixelforge.promptedit.dev/docs).

The app tracks every image from request to finished picture, keeps an exact ledger of every hold and charge, and updates the page live as images finish. Staff get an admin report that checks our records against Pixelforge's own usage figures, and a user and role management area.

## Contents

- [Tech stack](#tech-stack)
- [Requirements](#requirements)
- [Getting started](#getting-started)
- [Configuration](#configuration)
- [Running the app](#running-the-app)
- [Demo accounts](#demo-accounts)
- [Roles and permissions](#roles-and-permissions)
- [How it works](#how-it-works)
- [Project structure](#project-structure)
- [Testing and code quality](#testing-and-code-quality)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)

## Tech stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.4, Laravel 13 |
| Frontend | Vue 3, Inertia.js v3, TypeScript, Tailwind CSS 4, Vite |
| Auth | Laravel Fortify (sign-up, login, password reset, 2FA backend) |
| Roles | spatie/laravel-permission |
| Real-time | Laravel Reverb (WebSockets) with Laravel Echo |
| Queue, cache, sessions | Database drivers by default |
| Tests | Pest (PHPUnit-style test classes) |

## Requirements

- PHP 8.4 with the usual Laravel extensions (`pdo_mysql` or `pdo_sqlite`, `mbstring`, `openssl`, `curl`, `intl`)
- Composer 2
- Node.js 22 or newer, and npm
- MySQL 8 (or SQLite for a quick local setup)
- A Pixelforge API key and webhook signing secret

[Laravel Herd](https://herd.laravel.com) covers PHP, Composer, Node and the database on macOS and Windows. With Herd, the site is served at `http://pixelforge.test` and you don't need `php artisan serve`.

## Getting started

### 1. Clone the repository

```bash
git clone <repository-url> pixelforge
cd pixelforge
```

### 2. Install the dependencies

```bash
composer install
npm install
```

### 3. Create your environment file

```bash
cp .env.example .env
```

On Windows (Command Prompt), use `copy .env.example .env`.

### 4. Generate the application key

```bash
php artisan key:generate
```

### 5. Set up the database

Create an empty database named `pixelforge` in MySQL, then check that the `DB_*` values in `.env` match your MySQL user and password.

If you'd rather use SQLite, set `DB_CONNECTION=sqlite` in `.env`, delete the other `DB_*` lines, and create an empty file at `database/database.sqlite`.

### 6. Fill in the Pixelforge and Reverb settings

Open `.env` and set `PIXELFORGE_API_KEY`, `PIXELFORGE_WEBHOOK_SECRET` and the three `REVERB_APP_*` values. Any random strings work for Reverb locally. See [Configuration](#configuration) for what each one does.

### 7. Run the migrations

```bash
php artisan migrate
```

This creates the tables and the built-in roles and permissions, so sign-up works straight away.

### 8. Seed the demo accounts

```bash
php artisan db:seed
```

This adds a customer and an admin you can log in with (see [Demo accounts](#demo-accounts)). Skip it if you'd rather sign up yourself.

### 9. Start the app

```bash
composer run dev
```

Open `http://localhost:8000`, or `http://pixelforge.test` if you use Herd.

### Shortcut

`composer run setup` does steps 2, 3, 4 and 7 in one go and also builds the frontend. You still need to create the database before running it, and fill in `.env` and seed afterwards.

## Configuration

All settings live in `.env`. These are the ones specific to this app:

| Variable | Purpose |
| --- | --- |
| `APP_URL` | The URL you open the app at. Must match, or links and broadcasting auth break. |
| `PIXELFORGE_BASE_URL` | The Pixelforge API base URL. Defaults to `https://pixelforge.promptedit.dev/v1`. |
| `PIXELFORGE_API_KEY` | Your Pixelforge API key. Usage is tracked per key, so use one key per environment. |
| `PIXELFORGE_WEBHOOK_SECRET` | The signing secret for that key's webhooks. Messages with a bad signature are rejected. |
| `PIXELFORGE_MARKUP_PERCENT` | The markup on Pixelforge's cost. Defaults to `25`. |
| `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` | Credentials for the Reverb WebSocket server. Locally, any random strings will do. |
| `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Where the browser connects for live updates. `localhost`, `8080` and `http` work locally. |

The `VITE_REVERB_*` values are copied from the `REVERB_*` ones and baked into the frontend build, so run `npm run build` again (or restart `npm run dev`) after changing them.

Pixelforge's account limits are 60 requests a minute, 3 images at once and 500 images a day, shared across all your keys. The app keeps to the 3-at-once limit itself (`concurrency_limit` in `config/services.php`).

### Webhooks in local development

Pixelforge sends a webhook when an image finishes. It can't reach `localhost`, so locally you have two options:

- **Do nothing.** The scheduler checks on late images every minute, so images still finish. They just appear up to a minute later.
- **Expose your machine** with `herd share`, ngrok or a similar tunnel, then set the webhook URL for your key in the Pixelforge dashboard to `https://<your-tunnel>/webhooks/pixelforge`.

## Running the app

```bash
composer run dev
```

This starts five processes together:

| Process | Command | What it does |
| --- | --- | --- |
| server | `php artisan serve` | Serves the app on port 8000 (not needed with Herd) |
| queue | `php artisan queue:listen` | Sends images to Pixelforge and processes webhooks |
| vite | `npm run dev` | Frontend dev server with hot reload |
| reverb | `php artisan reverb:start` | WebSocket server for live status updates |
| schedule | `php artisan schedule:work` | The safety nets: late-image checks, waiting images, webhook retries |

The queue worker and the scheduler are both required. Without the queue, images stay queued. Without the scheduler, images that miss their webhook never finish, and images waiting for a free slot only move when another image finishes.

## Demo accounts

`php artisan db:seed` creates two accounts. Both use the password `password`.

| Email | Roles | Notes |
| --- | --- | --- |
| `test@example.com` | Customer | Starts with $10.00 of credit |
| `admin@example.com` | Admin and Customer | Sees the staff tools as well as the customer pages |

Run the seeder once on a fresh database; it fails if these emails already exist. To start over: `php artisan migrate:fresh --seed`.

## Roles and permissions

Every route checks a permission, and the sidebar only shows the pages a user can open.

| Permission | Unlocks | Customer | Admin |
| --- | --- | :---: | :---: |
| View dashboard | Dashboard | Yes | |
| Generate images | Generate page, live price, sending images | Yes | |
| View history | History page and each image | Yes | |
| Manage credits | Balance, purchases, buying credits | Yes | |
| View reports | The customer's own transactions | Yes | |
| View admin report | Revenue, cost and margin, checked against Pixelforge | | Yes |
| Manage users | Creating users and changing their access | | Yes |
| Manage roles | Creating, editing and deleting roles | | Yes |

- **New sign-ups** get the Customer role automatically.
- **An admin who also wants to generate images** needs both roles. The seeded admin has both.
- **Built-in roles** are defined in `app/Enums/User/RoleName.php`. `RolesAndPermissionsSeeder` resets them to those permissions, so they can be given to users but not edited in the app. Custom roles made in the app are fully editable.
- **Nobody can change their own roles or permissions,** so an admin can't lock themselves out.
- **After signing in,** each user lands on the first page they're allowed to open: the admin report for admins, the dashboard for customers.

Permissions are defined in `app/Enums/User/PermissionName.php`. To add one, add a case there, then run `php artisan db:seed --class=RolesAndPermissionsSeeder`.

## How it works

### Money is exact

- **Integers only:** every amount is stored as an integer number of nanodollars (1 dollar = 1,000,000,000). There are no floats anywhere in the money path.
- **Read as text:** Pixelforge's decimal strings are parsed as text so nothing is lost (`app/Support/Money/NanoDollars.php`). Rounding only happens when an amount is shown to a person.
- **Price formula:** a user's charge is Pixelforge's cost plus the markup, rounded up to the next nanodollar (`app/Services/Billing/PricingService.php`).
- **No stored balance:** the balance is always worked out from credit purchases plus the ledger.

### Holds, charges and releases

1. **Hold:** when a user sends an image, the most it can cost, plus a small safety margin, is held from their balance. The user's row is locked while the balance is checked, so two requests can't spend the same money.
2. **Charge and release:** when the image finishes, the real price is charged and the hold is released. A failed image is charged nothing.
3. **One line of each kind:** the ledger allows only one hold, one charge and one release per image, enforced by a unique index. Settling the same image twice changes nothing.
4. **Idempotent purchases:** credit purchases carry an idempotency key, so a double-click adds credits once.

### Sending images

- **Slots:** new images wait for a free slot, because Pixelforge allows 3 at a time. A queued job claims free slots and sends the next image.
- **Retries are safe:** each image's UUID goes to Pixelforge as the `Idempotency-Key`, so a retried send never creates a second image.
- **When Pixelforge is busy,** the job is released back onto the queue with a delay instead of blocking a worker.

### Webhooks, with polling as a safety net

- **Endpoint:** `POST /webhooks/pixelforge` skips the session and CSRF middleware. It checks the signature and timestamp, saves the event once by its ID, and returns quickly.
- **Processing:** the real work happens in a queued job. Duplicate deliveries are ignored.
- **Scheduled safety nets:** every minute the scheduler checks on images we haven't heard about for a while, and sends any waiting images. Every five minutes it retries webhook events that were saved but never processed.

### Live updates

When an image changes, the server broadcasts a small "something changed" message on the user's private channel. The page then reloads its data from the server rather than trusting the message. It also catches up after a reconnect and when the tab comes back into view, so a missed message is never a problem. There is no polling from the browser.

### Admin report

The admin report shows revenue, cost and margin per UTC day, and compares each day with Pixelforge's `GET /v1/usage` figures for the API key. Days are marked as matched, mismatched or still settling.

## Project structure

Code is grouped by domain (Billing, Generations, User, Webhooks), and the same groups appear in every layer:

```
app/
├── Enums/<Group>/                  Statuses, sizes, roles, permissions
├── Events/ and Listeners/<Group>/
├── Http/
│   ├── Controllers/<Group>/        Thin: validate, call a service or repository, render
│   ├── Requests/<Group>/           Form validation
│   └── Resources/<Group>/          What each page receives
├── Jobs/<Group>/                   Queued and scheduled work
├── Models/<Group>/
├── Policies/<Group>/
├── Repositories/<Group>/           All database queries
├── Repositories/Contracts/<Group>/ Repository interfaces (bound in RepositoryServiceProvider)
├── Services/<Group>/               Business logic
├── Services/Pixelforge/            The Pixelforge HTTP client
├── Support/                        Data objects, exceptions, money helpers
└── Traits/
resources/js/
├── components/ui/                  The ui kit: build every screen from these
├── components/<group>/             Feature components
├── layouts/                        AppLayout, AuthLayout, GuestLayout
├── pages/                          Inertia pages
└── types/
```

Business logic lives in services and data access in repositories. There are no action classes. Fortify's contracts are implemented by `UserService` and `PasswordService`.

Read these before contributing:

- [dev_kit/coding_standards.md](dev_kit/coding_standards.md): architecture, folder layout, money rules, PHP style and tests
- [dev_kit/ui_standards.md](dev_kit/ui_standards.md): the design system, the ui kit, forms and page layout

## Testing and code quality

```bash
composer test                     # the whole test suite
php artisan test --compact --filter=Dashboard   # a subset
npm run types                     # TypeScript and Vue type checking
npm run build                     # production frontend build
vendor/bin/pint                   # PHP code style
```

- **No real API calls:** tests run against an in-memory SQLite database, and outgoing HTTP is faked with `Http::fake()`, so the suite never calls Pixelforge.
- **Before opening a pull request:** run the tests, `npm run types` and Pint.

## Deployment

The app is built for [Laravel Cloud](https://cloud.laravel.com) but runs on any standard Laravel host. In production you need:

1. **A web process** serving `public/`.
2. **At least one queue worker:** `php artisan queue:work`.
3. **The scheduler:** `php artisan schedule:run` every minute. Jobs use `onOneServer()`, so the cache must be shared between servers.
4. **Reverb:** `php artisan reverb:start`, reachable at the `REVERB_HOST` and port the browser uses.
5. **On every deploy:**

   ```bash
   php artisan migrate --force
   php artisan db:seed --class=RolesAndPermissionsSeeder --force
   npm ci && npm run build
   php artisan optimize
   ```

   The seeder is safe to run every time. It adds new permissions and resets the built-in roles.

6. **Pixelforge:** set the webhook URL for the production API key to `https://<your-domain>/webhooks/pixelforge`, and put that key's signing secret in `PIXELFORGE_WEBHOOK_SECRET`.

## Troubleshooting

| Problem | Fix |
| --- | --- |
| "Unable to locate file in Vite manifest" | Run `npm run build`, or keep `npm run dev` running. |
| Images stay "Queued" | The queue worker or the scheduler isn't running. Start them with `composer run dev`. |
| Status doesn't update without a refresh | Reverb isn't running, or the `VITE_REVERB_*` values don't match `.env`. Rebuild after changing them. |
| Images finish about a minute late locally | Expected without a webhook tunnel. The scheduler picks them up instead. |
| 403 on every page after signing in | The account has no role. Give it Customer from Users, or run the roles seeder. |
| Webhooks rejected with 400 | `PIXELFORGE_WEBHOOK_SECRET` doesn't match the secret for the key that sent them. |
