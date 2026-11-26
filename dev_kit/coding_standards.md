# Coding standards

How code in PixelForge is organised and written. When in doubt, look at the sibling files and do what they do.

## Architecture

The backend uses repositories and services. There are no action classes and no `app/Actions` folder.

| Layer | Responsibility |
| --- | --- |
| Controllers | Thin. Validate with a form request, call a service (writes) or a repository (reads), return a page or redirect. |
| Form requests | All input validation. `authorize()` returns `true` when the route already checks access. |
| Services | Business logic: pricing, holds and charges, sending images, webhooks. Every class is named `XService`. |
| Repositories | All database queries. Each has an interface in `Repositories/Contracts`, bound in `RepositoryServiceProvider`. |
| Resources | Shape exactly what a page receives. Money is formatted here, never in Vue. |
| Policies | Per-record access, such as "users can only see their own images". |
| Jobs | Queued work and the scheduled safety nets in `routes/console.php`. |

- **Type-hint the interface:** inject repository interfaces, not concrete classes.
- **Small helpers become methods:** a small single-purpose helper becomes a method on its group's service, not a new class.
- **Fortify's contracts** are implemented by `UserService` and `PasswordService`.

## Folder layout

Code is grouped by domain, and the same groups appear in every layer:

**Billing · Generations · User · Webhooks**

```
app/Models/Billing/LedgerEntry.php
app/Repositories/Billing/LedgerEntryRepository.php
app/Repositories/Contracts/Billing/LedgerEntryRepositoryInterface.php
app/Services/Billing/BalanceService.php
app/Http/Controllers/Billing/CreditController.php
database/factories/Billing/LedgerEntryFactory.php
```

- **Where things go:** put a new class in the matching group folder of each layer. If nothing fits, agree on a new group name and use it across all layers. Don't drop files at the root of a layer.
- **The Pixelforge HTTP client** lives in `app/Services/Pixelforge/`. It's the one vendor-named folder.
- **`app/Support/` is for anything that isn't a service,** organised by kind, then group: `Support/Data/<Group>/` for data objects, `Support/Exceptions/<Group>/` for exceptions, `Support/Money/` for money helpers.
- **Traits** go in `app/Traits/`, flat. Never create a `Concerns/` folder.
- **Pixelforge settings,** including the markup, live in the `pixelforge` block of `config/services.php`. Don't add separate config files.

## Money

Money is the part of this app that must never be wrong.

- **Integers only:** store and calculate every amount as an integer number of **nanodollars** (`1 dollar = 1_000_000_000`). Never use floats.
- **Parse as text:** read Pixelforge's decimal strings with `NanoDollars::fromDecimalString()`.
- **Round only for display:** format with `NanoDollars::format()` in resources.
- **Price calculations** belong in `PricingService`.
- **No stored balance:** the balance is always worked out from purchases and the ledger.
- **Ledger writes** go through `LedgerEntryRepository::recordOnce()`. The database allows one hold, one charge and one release per image, so a retried write can't double-charge.
- **Anything that checks the balance and then spends it** runs in a transaction with the user's row locked (`lockForUpdate`).
- **Anything that might be retried** (sending images, buying credits, webhooks) must be idempotent.

## PHP style

- PHP 8.4, Laravel 13. Run Pint before pushing: `vendor/bin/pint`.
- Always use curly braces, even for one-line `if` bodies.
- Use constructor property promotion: `public function __construct(private BalanceService $balance) {}`.
- Give every method parameter a type and every method a return type.
- Enum cases use TitleCase: `PermissionName::ViewHistory`.
- Use array shapes in PHPDoc for arrays: `@return array{succeeded: int, unfinished: int}`.
- Use descriptive names: `isRegisteredForDiscounts()`, not `discount()`.
- Use named routes and `route()` when building URLs in PHP.
- Create files with `php artisan make:*` where a generator exists, then move them into the right group folder.

## Comments

Comments should read like a person on the team wrote them.

- **Explain why, not what.** If the code is obvious, leave it bare.
- **Keep them short and plain.** "Pixelforge wants an answer within 10 seconds, so we keep this quick."
- **No narrating changes** ("Added to fix…"), no "Note:" or "IMPORTANT", and no restating the method name.
- **Docblocks on classes and public methods** say what they're for in a sentence or two.

## Roles and permissions

- **Permissions are code:** every one is a case in `app/Enums/User/PermissionName.php`, with a label and a description.
- **Built-in roles** are in `RoleName.php`, and `RolesAndPermissionsSeeder` keeps the database in sync. It runs on every deploy.
- **Routes** check permissions with `->can(PermissionName::X->value)` or `middleware('can:...')`.
- **Per-record rules** go in a policy.
- **Share a flag for every page link** in `HandleInertiaRequests` (`auth.can.*`) so the frontend can hide links. The route still does the real check.

## Frontend

- **Stack:** Vue 3 with `<script setup lang="ts">`, Inertia v3 and TypeScript.
- **Pages** live in `resources/js/pages/<group>/`, and their props are typed in `resources/js/types/<group>.ts`.
- **Logic stays on the server:** components display what the backend sends. Prices, permissions and totals are worked out on the server.
- **UI rules** are in [ui_standards.md](ui_standards.md).

## Tests

- **Write a test** for every behaviour change, validation rule or permission check you add. Pure styling changes don't need one.
- **Feature tests first,** in `tests/Feature/<Layer>/<Group>/`, for example `tests/Feature/Http/Billing/ReportControllerTest.php`.
- **Style:** tests are PHPUnit-style classes run by Pest, named as sentences: `test_customers_see_only_their_own_transactions`.
- **Test data:** build it with factories and their states (`Generation::factory()->succeeded()`, `User::factory()->admin()`). New models get a factory.
- **Never call Pixelforge for real.** Fake it with `Http::fake()` (see `tests/Traits/FakesPixelforge.php`).
- **What to assert:** the result the user sees (status, page props, redirect) and what was saved.
- **Run the smallest relevant set while working** (`php artisan test --compact --filter=Report`), and the whole suite before pushing.

## Before you push

```bash
vendor/bin/pint    # PHP style
composer test      # all tests pass
npm run types      # TypeScript passes
npm run build      # frontend builds
```

Don't commit `.env`, credentials or files under the AI tooling folders (they're in `.gitignore`).
