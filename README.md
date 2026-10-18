# Salary-Linked Access

A working prototype of a salary-linked liquidity platform for Oman. Employees take small advances against their verified salary. A licensed bank pays the advance out and collects it on payday by direct debit, and an approved funder holds the receivable.

The product context is in [docs/product-brief.md](docs/product-brief.md), and the source decks are in [business/](business/). The portfolio review holds this product at a **regulatory gate**, so this build uses fake bank, funder and payroll drivers. No money moves.

## Stack

- Laravel 13, Inertia 3, React 19, TypeScript, Tailwind 4, shadcn/ui (the Laravel React starter kit)
- SQLite and the database queue
- English and Arabic (right-to-left)

## Getting started

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
composer run dev
```

Then open http://127.0.0.1:8000.

`composer run dev` starts the web server, the queue worker, the log viewer and Vite. The queue worker is required, because payouts and collections run as queued jobs.

Demo accounts and a walkthrough are in [docs/demo-script.md](docs/demo-script.md). Every account's password is `password`.

## Portals

| Path | Who | What |
|---|---|---|
| `/app` | Employees | Mobile-first: onboarding (IBAN and mandate), available limit, request an advance, history |
| `/employer` | HR administrators | Roster, CSV import, invitations, enrolment approval, totals-only dashboard |
| `/admin` | Platform team | Employers, partners, program terms, disclosures, the advance book, audit log, simulation console |
| `/partner` | Bank and funder viewers | Read-only book, ageing, exposure by employer, CSV export |

## Where things live

- **`app/Domain`** holds the business rules:
  - eligibility and limits (`Eligibility/EligibilityService`)
  - the advance lifecycle (`Advances/AdvanceLifecycle`)
  - requesting an advance (`Advances/RequestAdvance`)
  - pay cycles (`Payroll/PayCycleCalendar`)
  - onboarding and the roster
- **`app/Contracts`** has the bank, funder and payroll gateways. **`app/Services/Fake`** has their demo drivers and forced-failure scenarios, and **`config/salary_access.php`** chooses the drivers.
- **`app/Support/Money`** handles money as integer baisa (1 OMR = 1000 baisa). **`app/Support/Clock`** is the demo clock the simulation console moves.
- **`lang/ar.json`** holds the Arabic strings. `tests/Feature/ArabicTranslationsTest.php` fails if a string in the interface has no translation.

## Checks

```bash
php artisan test
vendor/bin/phpstan analyse
vendor/bin/pint
npm run check && npm run types:check
```

A daily scheduled command, `advances:collect` at 06:00, collects advances on payday and retries failed collections before marking them overdue.
