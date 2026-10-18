# Demo script: Zarf (ظرف)

A 10–15 minute walkthrough of every portal. It shows the full life of an advance: invitation, onboarding, HR approval, request, payout, payday collection and a failed collection. It ends with the bank's view of it all.

> **This is a prototype.** No money moves. Every bank, funder and payroll call goes to a fake driver (`app/Services/Fake`). Say so at the start of any external demo.

## Before you start

```bash
php artisan migrate:fresh --seed   # reset to the demo data
composer run dev                   # web server, queue worker, logs and Vite
```

Open http://127.0.0.1:8000. The queue worker must be running: payouts and collections are queued jobs.

To reset at any time, run `php artisan migrate:fresh --seed`. This also resets the demo clock and any forced failures.

## Demo accounts

All accounts use the password **`password`**.

| Role                            | Email                    | Notes                                   |
| ------------------------------- | ------------------------ | --------------------------------------- |
| Platform admin                  | `admin@demo.test`        | Everything, plus the simulation console |
| HR, Al Noor Trading LLC         | `hr@alnoor.demo.test`    | Payday on the 25th                      |
| HR, Majan Logistics SAOC        | `hr@majan.demo.test`     | Payday on the 28th                      |
| Bank viewer                     | `bank@demo.test`         | Demo National Bank                      |
| Funder viewer                   | `funder@demo.test`       | Demo Capital Partners                   |
| Employee (enrolled)             | `salim@alnoor.demo.test` | OMR 800 salary, so an OMR 150 limit     |
| Employee (waiting for approval) | `aisha@alnoor.demo.test` | Onboarded; HR has not approved yet      |

**Invitation for a new employee:** Khalid Al Hinai has not created an account yet.

- Link: `/invitation/demo-invite-khalid`
- Last 4 digits of his civil ID: **8061**

All companies, people and partners are fictional.

## 1. The employee (phone width, about 4 minutes)

Use a narrow browser window or the browser's device toolbar.

1. Open `/invitation/demo-invite-khalid`. Khalid confirms the last 4 digits of his civil ID (**8061**) and chooses a password.
2. **Get set up** has three steps:
    - **IBAN:** enter `OM60 0180 0000 1234 5678 901`. Any valid Omani IBAN works; an invalid checksum is rejected.
    - **Sign mandate:** the fake bank registers the direct-debit mandate, and payroll confirms his salary.
    - **Approval:** Khalid now waits for HR.
3. Log out and sign in as **Salim**.
4. Point out the home screen:
    - **OMR 150.000 available.** That's 18.75% of his OMR 800 salary, the example from the business case.
    - The next payday date.
    - The card in the bank partner's colour (the white-label idea).
5. **Get an advance:**
    - Pick OMR 75. The summary shows the fixed OMR 2.500 fee and the OMR 77.500 collected on payday.
    - Accept the versioned terms and confirm.
6. The advance page updates itself: **Approved → Sending to your bank → Paid out**.
7. Switch to **العربية** (top bar). The whole interface flips to right-to-left Arabic.

## 2. The employer's HR (desktop, about 3 minutes)

Sign in as `hr@alnoor.demo.test`.

1. **Dashboard:** only totals, never individual advances. Advances are private to staff, and collections come from their own bank accounts, not from payroll.
2. **Waiting for your approval:** open Khalid and **Approve**. He can now take advances.
3. **Employees → Import CSV:** upload `docs/sample-roster.csv`. Three rows are added and invited. The two bad rows (missing email, duplicate staff number) are reported by line number.
4. Open an employee and set **Employment status → On notice**. They can no longer take new advances; anything already paid out is still collected on payday.

## 3. The platform team and payday (about 4 minutes)

Sign in as `admin@demo.test`.

1. **Dashboard:** the whole book, this month's payouts and collections, and the advances that need attention.
2. **Simulation → Force a failure:** choose Salim, **Payday direct debit**, times = **1**.
3. Click **Al Noor Trading LLC payday**. The demo clock jumps to the 25th, and a banner shows the simulated date on every page.
4. **Run now.** Salim's collection fails and is scheduled for a retry in two days. Every other due advance is collected.
5. Click **+3 days**, then **Run now** again. The retry succeeds.
6. Open **Advances**, then Salim's advance:
    - the audit trail
    - the bank transactions (one failed debit, one successful)
    - the actions the lifecycle allows
7. To show **Program terms** and **Disclosures**: change the fee, or publish a new disclosure version. New advances must accept the new version; older advances keep the version they accepted.
8. To show the white-label idea:
    - **Partners →** edit Demo National Bank's brand colour.
    - Sign in as Salim again; his home card now uses the new colour.

Click **Back to today** in the simulation console when you finish.

## 4. The bank (about 2 minutes)

Sign in as `bank@demo.test`.

1. **Dashboard:**
    - the funded book
    - overdue amounts by days past due
    - exposure by employer
    - payouts and collections by month (hover a month, or open the table view)
2. **Advances → Export CSV** downloads the filtered list for reconciliation.

The funder viewer (`funder@demo.test`) sees the same views, scoped to the advances it funds.

## Talking points

- **Separation of roles.** AWJ runs onboarding, eligibility, disclosures and the record of every advance. The licensed bank moves the money; the funder holds the receivable. The integrations are interfaces with fake drivers today (`app/Contracts`).
- **Everything is configurable,** per employer as well: the limit ratio, cap, fee, minimum tenure, request cut-off and number of advances per cycle. Pricing is still a placeholder until the regulatory classification is settled.
- **Audit trail.** Every status change, approval and admin action is recorded.

## Known gaps in the prototype

- Employees sign in with email and password. Phone/OTP login, which is common in Oman, is not built yet.
- The account settings pages from the starter kit (profile, password, appearance) are still English-only.
- Email goes to the log (`MAIL_MAILER=log`). In the demo, the invitation link is copied from the employee's page in the HR portal.
- There are no real bank, funder or WPS integrations, and no Sharia-compliant fee model yet. `FeeModel` is where one would go.
