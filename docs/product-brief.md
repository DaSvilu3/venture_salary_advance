# Zarf (ظرف) — Product Brief

Extracted from `business/Product Evaluation One-Pagers (EN).pdf` (Portfolio Review, October 2026, Concept 02).
An Arabic version of the same deck is at `business/Product Evaluation One-Pagers (AR).pdf`.

> **Status in the portfolio review: Hold · regulatory gate (weighted score 3.4 / 5).**
> The review recommends building **only a clickable prototype** until a licensed bank and an approved funder have signed.
> Settle the legal structure first.

**Brand name: Zarf (ظرف)**, chosen October 2026. It means both "envelope" (the pay envelope) and "circumstance"; tagline "Ready for every situation." / «لكل ظرف.». The logo and the alternatives considered are in `docs/logo-concepts/`. The deck's working name was "Salary-Linked Access". Typefaces: Plus Jakarta Sans (English) and Readex Pro (Arabic); the pairings compared are in `docs/logo-concepts/font-pairings.png`.

## What it is

A salary-linked liquidity platform (B2B2C fintech). Employees draw a small, short-term advance against their verified salary.
The money lands in their own bank account, and the bank collects it on the due date by **direct debit under a mandate**.

Worked example from the source: a salary of OMR 800 gives a limit of OMR 150, and the employee draws OMR 100.

### Separation of roles

| Party               | Responsibility                                                                                                              |
| ------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| **Platform (AWJ)**  | Onboarding, employer verification, eligibility, disclosures. AWJ stays the technology provider only.                        |
| **Licensed bank**   | Moves the money: disbursement, settlement, direct-debit mandates.                                                           |
| **Approved funder** | Provides capital and holds the receivable.                                                                                  |
| **Employer / HR**   | Verifies employment and salary; offers the product as a staff financial-wellness benefit. This is the distribution channel. |
| **Employee**        | Requests and receives advances; repays by direct debit.                                                                     |

### Existing rails to integrate with (Oman)

- ACH (Automated Clearing House)
- WPS (Wage Protection System)
- CBO Direct Debit and Mandate Management System

## Target customers

- **Employees:** about 2.65M workers in Oman (NCSI 2026). The real market is employees with regular salaries at participating employers.
- **Employers and HR:** private firms that offer it as a financial-wellness benefit for staff.
- **Banks and funders:** partners for mandates, settlement and capital. A bank may also buy the platform white-label.

## Revenue model

1. **Fee per advance:** only where the regulatory classification allows it, and always fully disclosed.
2. **Employer subscription:** dashboards and financial-wellness tools.
3. **Revenue share with the licensed funder.**
4. **Later:** a merchant marketplace, kept separate from funding.

## Possible sales · Oman (OMR platform revenue per year, scenario estimates)

| Scenario     | Year-3 usage (average advance OMR 100)                  | Year 1 | Year 3 |
| ------------ | ------------------------------------------------------- | ------ | ------ |
| Conservative | 15k enrolled · 25% use monthly · ≈45k advances a year   | 0      | 90k    |
| Base         | 60k enrolled · 30% use monthly · ≈216k advances a year  | 15k    | 540k   |
| Upside       | 150k enrolled · 35% use monthly · ≈630k advances a year | 40k    | 1.9M   |

Assumptions:

- Platform net take per advance is OMR 2 / 2.5 / 3, pending pricing rules.
- Year-3 funds disbursed are about OMR 4.5M / 22M / 63M.
- Capital outstanding is about OMR 0.4M / 1.8M / 5.3M on a one-month cycle.
- Year 1 is regulatory work plus a pilot.
- First revenue arrives 12–18 months out, after regulatory clarity.
- Upfront effort is **high**: legal structure, bank and funder deals, capital.

## Strengths

- A real, frequent need with repeat monthly use. The employer channel keeps acquisition cost low.
- The rails already exist (ACH, WPS, CBO Direct Debit and Mandate Management System).
- The design already separates tech, funding and banking, so AWJ can stay the technology provider.

## Risks

- **Likely treated as credit.** That would require a licensed lender, and could bring pricing caps and the need for a Sharia-compliant variant.
- **Not first to market.** ABHI already offers earned-wage access in Oman, and banks sell salary-linked cards and loans.
- **Capital and collection risk.** Mandates can be cancelled, employees can leave, and salaries can be delayed. Needing sign-off from three parties delays revenue.

## Scorecard (1–5, 5 = best; regulatory: 5 = lowest risk)

| Criterion               | Weight | Score   |
| ----------------------- | ------ | ------- |
| Market need             | 20%    | 5       |
| Revenue potential       | 20%    | 4       |
| Speed to revenue        | 15%    | 2       |
| Regulatory & legal risk | 15%    | 2       |
| Build feasibility (AWJ) | 10%    | 4       |
| Defensibility           | 10%    | 3       |
| Strategic fit           | 10%    | 3       |
| **Weighted**            |        | **3.4** |

## Recommendation from the review

Hold the build and settle the legal structure first:

1. Get a legal opinion and hold a CBO pre-consultation on classification, lender identity and pricing.
2. Pitch one bank a white-label version for its own salary customers. This is the fastest licensed route.
3. Study ABHI's Oman model and pricing, as a benchmark or as a possible partner.
4. Build only a clickable prototype until a bank and a funder have signed.

## Implications for this codebase

These notes come from development, not from the source deck.

- Treat the first build as a **prototype**. No real money movement: bank, funder and mandate integrations sit behind interfaces with fake or sandbox drivers.
- Keep fees, limit ratios and the advance cycle **configurable**, because pricing rules and caps are not settled yet.
- Support **bilingual (English / Arabic, RTL)** from the start. The business material already exists in both languages.
- Plan for a white-label or multi-tenant deployment, since a bank may license the platform.
- Use OMR amounts with 3 decimal places (baisa). Store money as integer baisa.

## Missing source material

The deck cites the _"Salary-Linked Financial Access Platform concept and regulatory framework"_ (with NCSI and CBO references). That document is **not in this repo**. Add it to `business/` for the detailed flows, eligibility rules and regulatory notes.
