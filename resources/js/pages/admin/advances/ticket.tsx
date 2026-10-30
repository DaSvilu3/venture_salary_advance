import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer, ShieldCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { Button } from '@/components/ui/button';
import { advanceStatusMeta, type StatusTone } from '@/lib/advance-status';
import { useDates } from '@/lib/format';
import { useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { show } from '@/routes/admin/advances';
import type { AdvanceStatus } from '@/types';

type Party = { name: string; code: string };

type Props = {
    advance: {
        reference: string;
        status: AdvanceStatus;
        amount: number;
        fee: number;
        total_due: number;
        due_date: string;
        requested_at: string;
        disbursed_at: string | null;
        collected_at: string | null;
        disclosure_accepted_at: string | null;
        collection_attempts: number;
        next_collection_attempt_on: string | null;
        failure_reason: string | null;
    };
    employee: {
        name: string;
        staff_no: string;
        masked_civil_id: string;
        masked_iban: string | null;
        net_salary: number;
        employment_start_date: string;
        employment_status: string;
        salary_verified_at: string | null;
    };
    employer: { name: string; cr_number: string };
    bank: Party | null;
    funder: Party | null;
    mandate: {
        reference: string;
        status: string;
        signed_at: string | null;
        max_amount: number;
    } | null;
    disclosure: { version: string; body: string };
    terms: {
        limit_ratio_bps: number;
        fee_model: string;
        flat_fee: number;
        limit: number;
    };
    payCycle: { period_start: string; payday: string } | null;
    timeline: {
        id: number;
        action: string;
        actor: string | null;
        at: string | null;
    }[];
    transactions: {
        id: number;
        type: string;
        status: string;
        amount: number;
        reference: string | null;
        failure_reason: string | null;
        at: string;
    }[];
    generated: { on: string; by: string | null };
};

const toneLight: Record<StatusTone, string> = {
    neutral: 'bg-zinc-100 text-zinc-700',
    progress: 'bg-sky-100 text-sky-800',
    success: 'bg-emerald-100 text-emerald-800',
    warning: 'bg-amber-100 text-amber-900',
    danger: 'bg-red-100 text-red-800',
};

const printStyles = `
@media print {
    @page { size: A4; margin: 14mm; }
    html, body { background: #ffffff !important; }
    .no-print { display: none !important; }
    .ticket-sheet { box-shadow: none !important; border: 0 !important; margin: 0 !important; max-width: none !important; width: 100% !important; padding: 0 !important; }
    .ticket-section { break-inside: avoid; }
}
.ticket-sheet * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
`;

function parseDay(value: string): Date {
    return new Date(value.length <= 10 ? `${value}T00:00:00` : value);
}

function daysBetween(from: string, to: string): number {
    const ms = parseDay(to).getTime() - parseDay(from).getTime();

    return Math.max(1, Math.round(ms / 86_400_000));
}

function percent(ratio: number): string {
    return `${(ratio * 100).toFixed(1)}%`;
}

function Field({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div>
            <dt className="text-[11px] font-medium tracking-wide text-zinc-500 uppercase">
                {label}
            </dt>
            <dd className="mt-0.5 text-sm font-medium text-zinc-900">
                {value}
            </dd>
        </div>
    );
}

function Section({
    number,
    title,
    children,
}: {
    number: number;
    title: string;
    children: ReactNode;
}) {
    return (
        <section className="ticket-section break-inside-avoid border-t border-zinc-200 pt-5">
            <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold text-zinc-900">
                <span className="flex size-5 items-center justify-center rounded-full bg-brand-soft text-[11px] font-bold text-brand-soft-foreground">
                    {number}
                </span>
                {title}
            </h2>
            {children}
        </section>
    );
}

export default function AdvanceTicket({
    advance,
    employee,
    employer,
    bank,
    funder,
    mandate,
    disclosure,
    terms,
    payCycle,
    timeline,
    transactions,
    generated,
}: Props) {
    const t = useT();
    const money = useMoney();
    const { date, dateTime } = useDates();

    const statusMeta = advanceStatusMeta[advance.status];
    const termStart = advance.disbursed_at ?? advance.requested_at;
    const termDays = daysBetween(termStart, advance.due_date);
    const feeRatio = advance.amount > 0 ? advance.fee / advance.amount : 0;
    const annualised = feeRatio * (365 / termDays);

    const employmentStatusLabels: Record<string, string> = {
        active: t('Active'),
        on_notice: t('On notice'),
        terminated: t('Terminated'),
    };
    const mandateStatusLabels: Record<string, string> = {
        active: t('Active'),
        pending: t('Pending'),
        cancelled: t('Cancelled'),
    };
    const feeModelLabels: Record<string, string> = {
        flat: t('Fixed fee'),
    };

    const compliancePoints: [string, string][] = [
        [
            t('Data minimisation'),
            t(
                'Civil ID and IBAN are masked; full identifiers are never shown.',
            ),
        ],
        [
            t('Consumer protection'),
            t(
                'A single fixed fee, disclosed before the advance and shown here. The employee accepted a versioned statement of terms, kept with the advance.',
            ),
        ],
        [
            t('Audit integrity'),
            t(
                'Every approval and status change is kept in an append-only trail.',
            ),
        ],
        [
            t('Separation of roles'),
            t(
                'AWJ provides the technology only. A licensed bank moves the money; an approved funder holds the receivable.',
            ),
        ],
        [
            t('Prototype'),
            t(
                'No real money moves. Bank, funder and payroll steps are simulated.',
            ),
        ],
    ];

    return (
        <>
            <Head title={`${t('CBO record')} · ${advance.reference}`} />
            <style>{printStyles}</style>

            <div className="min-h-screen bg-zinc-100 py-8">
                <div className="mx-auto flex max-w-[210mm] flex-col gap-4 px-4">
                    {/* Screen-only toolbar */}
                    <div className="no-print flex flex-wrap items-center justify-between gap-2">
                        <Button asChild variant="outline">
                            <Link href={show(advance.reference)}>
                                <ArrowLeft className="rtl:rotate-180" />
                                {t('Back to advance')}
                            </Link>
                        </Button>
                        <div className="flex items-center gap-2">
                            <LocaleSwitcher />
                            <Button onClick={() => window.print()}>
                                <Printer />
                                {t('Print / Save as PDF')}
                            </Button>
                        </div>
                    </div>

                    {/* The document */}
                    <article className="ticket-sheet space-y-6 rounded-xl border border-zinc-200 bg-white p-8 text-zinc-900 shadow-sm md:p-10">
                        {/* Header */}
                        <header className="space-y-4">
                            <div className="flex items-start justify-between gap-4">
                                <div className="flex items-center gap-3">
                                    <div className="flex aspect-square size-11 items-center justify-center rounded-xl bg-brand-soft ring-1 ring-brand-soft-foreground/15">
                                        <AppLogoIcon className="size-6 fill-brand-soft-foreground" />
                                    </div>
                                    <div>
                                        <p
                                            className="text-lg font-bold"
                                            lang="ar"
                                        >
                                            ظرف{' '}
                                            <span className="text-zinc-400">
                                                · Zarf
                                            </span>
                                        </p>
                                        <p className="text-xs text-zinc-500">
                                            {t(
                                                'Salary-linked liquidity platform',
                                            )}
                                        </p>
                                    </div>
                                </div>
                                <span
                                    className={`rounded-full px-3 py-1 text-xs font-semibold ${toneLight[statusMeta.tone]}`}
                                >
                                    {t(statusMeta.label)}
                                </span>
                            </div>

                            <div className="rounded-lg bg-brand-soft/60 p-4">
                                <h1 className="text-xl font-bold text-zinc-900">
                                    {t('Salary Advance Record')}
                                </h1>
                                <p className="mt-0.5 text-sm text-brand-soft-foreground">
                                    {t(
                                        'Prepared for Central Bank of Oman review',
                                    )}
                                </p>
                            </div>

                            <dl className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                <Field
                                    label={t('Reference')}
                                    value={
                                        <span className="font-mono" dir="ltr">
                                            {advance.reference}
                                        </span>
                                    }
                                />
                                <Field
                                    label={t('Disclosure version')}
                                    value={disclosure.version}
                                />
                                <Field
                                    label={t('Generated on')}
                                    value={date(generated.on)}
                                />
                                <Field
                                    label={t('Prepared by')}
                                    value={generated.by ?? t('System')}
                                />
                            </dl>

                            <p className="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                {t(
                                    'Prototype — not a live financial service. No money moves; bank, funder and payroll steps are simulated.',
                                )}
                            </p>
                        </header>

                        {/* 1. Parties */}
                        <Section number={1} title={t('Parties')}>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <PartyCard
                                    role={t('Technology platform')}
                                    name="AWJ Ventures"
                                    detail={t('Technology provider only')}
                                />
                                <PartyCard
                                    role={t('Licensed bank')}
                                    name={bank?.name ?? '—'}
                                    detail={
                                        bank
                                            ? t('Moves the money · :code', {
                                                  code: bank.code,
                                              })
                                            : '—'
                                    }
                                />
                                <PartyCard
                                    role={t('Approved funder')}
                                    name={funder?.name ?? '—'}
                                    detail={
                                        funder
                                            ? t(
                                                  'Holds the receivable · :code',
                                                  {
                                                      code: funder.code,
                                                  },
                                              )
                                            : '—'
                                    }
                                />
                                <PartyCard
                                    role={t('Employer')}
                                    name={employer.name}
                                    detail={t('CR :number', {
                                        number: employer.cr_number,
                                    })}
                                />
                                <PartyCard
                                    role={t('Employee')}
                                    name={employee.name}
                                    detail={t('Staff :no · Civil ID :id', {
                                        no: employee.staff_no,
                                        id: employee.masked_civil_id,
                                    })}
                                />
                            </div>
                        </Section>

                        {/* 2. Eligibility basis */}
                        <Section number={2} title={t('Eligibility basis')}>
                            <dl className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                                <Field
                                    label={t('Verified net salary')}
                                    value={money(employee.net_salary)}
                                />
                                <Field
                                    label={t('Salary verified on')}
                                    value={
                                        employee.salary_verified_at
                                            ? date(employee.salary_verified_at)
                                            : '—'
                                    }
                                />
                                <Field
                                    label={t('Employment status')}
                                    value={
                                        employmentStatusLabels[
                                            employee.employment_status
                                        ] ?? employee.employment_status
                                    }
                                />
                                <Field
                                    label={t('Employment start')}
                                    value={date(employee.employment_start_date)}
                                />
                                <Field
                                    label={t('Limit ratio')}
                                    value={percent(
                                        terms.limit_ratio_bps / 10_000,
                                    )}
                                />
                                <Field
                                    label={t('Eligible limit')}
                                    value={money(terms.limit)}
                                />
                            </dl>
                            <p className="mt-3 text-xs text-zinc-500">
                                {t(
                                    'The advance is drawn against the employee’s verified salary, up to :ratio of net salary.',
                                    {
                                        ratio: percent(
                                            terms.limit_ratio_bps / 10_000,
                                        ),
                                    },
                                )}
                            </p>
                        </Section>

                        {/* 3. Advance terms & fee disclosure */}
                        <Section
                            number={3}
                            title={t('Advance terms & fee disclosure')}
                        >
                            <dl className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                                <Field
                                    label={t('Advance amount')}
                                    value={money(advance.amount)}
                                />
                                <Field
                                    label={t('Service fee')}
                                    value={money(advance.fee)}
                                />
                                <Field
                                    label={t('Total due on payday')}
                                    value={
                                        <span className="font-semibold">
                                            {money(advance.total_due)}
                                        </span>
                                    }
                                />
                                <Field
                                    label={t('Fee model')}
                                    value={
                                        feeModelLabels[terms.fee_model] ??
                                        terms.fee_model
                                    }
                                />
                                <Field
                                    label={t('Fee as share of advance')}
                                    value={percent(feeRatio)}
                                />
                                <Field
                                    label={t('Advance term')}
                                    value={t(':days days', { days: termDays })}
                                />
                            </dl>
                            <p className="mt-3 rounded-lg bg-zinc-50 px-3 py-2 text-xs text-zinc-600">
                                {t(
                                    'Indicative annualised cost :rate, shown for transparency only. The charge is a single fixed fee, not interest.',
                                    { rate: percent(annualised) },
                                )}
                            </p>

                            <div className="mt-4 rounded-lg border border-zinc-200">
                                <div className="flex items-center justify-between border-b border-zinc-200 bg-zinc-50 px-3 py-2 text-xs">
                                    <span className="font-semibold text-zinc-700">
                                        {t('Terms accepted by the employee')}
                                    </span>
                                    <span className="text-zinc-500">
                                        {t('Version :version', {
                                            version: disclosure.version,
                                        })}
                                        {advance.disclosure_accepted_at
                                            ? ` · ${dateTime(advance.disclosure_accepted_at)}`
                                            : ''}
                                    </span>
                                </div>
                                <p className="px-3 py-3 text-xs leading-relaxed whitespace-pre-line text-zinc-700">
                                    {disclosure.body}
                                </p>
                            </div>
                        </Section>

                        {/* 4. Direct-debit mandate */}
                        <Section number={4} title={t('Direct-debit mandate')}>
                            <dl className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                                <Field
                                    label={t('Mandate reference')}
                                    value={
                                        <span className="font-mono" dir="ltr">
                                            {mandate?.reference ?? '—'}
                                        </span>
                                    }
                                />
                                <Field
                                    label={t('Mandate status')}
                                    value={
                                        mandate
                                            ? (mandateStatusLabels[
                                                  mandate.status
                                              ] ?? mandate.status)
                                            : '—'
                                    }
                                />
                                <Field
                                    label={t('Signed on')}
                                    value={
                                        mandate?.signed_at
                                            ? date(mandate.signed_at)
                                            : '—'
                                    }
                                />
                                <Field
                                    label={t('Maximum authorised amount')}
                                    value={
                                        mandate
                                            ? money(mandate.max_amount)
                                            : '—'
                                    }
                                />
                                <Field
                                    label={t('Account (IBAN)')}
                                    value={
                                        <span className="font-mono" dir="ltr">
                                            {employee.masked_iban ?? '—'}
                                        </span>
                                    }
                                />
                                <Field
                                    label={t('Collecting bank')}
                                    value={bank?.name ?? '—'}
                                />
                            </dl>
                            <p className="mt-3 text-xs text-zinc-500">
                                {t(
                                    'Collection is by direct debit under the employee’s signed mandate (CBO Direct Debit and Mandate Management System). Simulated in this prototype.',
                                )}
                            </p>
                        </Section>

                        {/* 5. Repayment & collection */}
                        <Section number={5} title={t('Repayment & collection')}>
                            <dl className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                <Field
                                    label={t('Pay cycle start')}
                                    value={
                                        payCycle
                                            ? date(payCycle.period_start)
                                            : '—'
                                    }
                                />
                                <Field
                                    label={t('Due on')}
                                    value={date(advance.due_date)}
                                />
                                <Field
                                    label={t('Collection attempts')}
                                    value={String(advance.collection_attempts)}
                                />
                                <Field
                                    label={t('Next attempt')}
                                    value={
                                        advance.next_collection_attempt_on
                                            ? date(
                                                  advance.next_collection_attempt_on,
                                              )
                                            : '—'
                                    }
                                />
                                <Field
                                    label={t('Collected on')}
                                    value={
                                        advance.collected_at
                                            ? dateTime(advance.collected_at)
                                            : '—'
                                    }
                                />
                                <Field
                                    label={t('Repayment status')}
                                    value={t(statusMeta.label)}
                                />
                            </dl>

                            <table className="mt-4 w-full border-collapse text-sm">
                                <thead>
                                    <tr className="border-b border-zinc-200 text-start text-[11px] tracking-wide text-zinc-500 uppercase">
                                        <th className="py-2 pe-3 text-start font-medium">
                                            {t('When')}
                                        </th>
                                        <th className="py-2 pe-3 text-start font-medium">
                                            {t('Type')}
                                        </th>
                                        <th className="py-2 pe-3 text-start font-medium">
                                            {t('Result')}
                                        </th>
                                        <th className="py-2 pe-3 text-end font-medium">
                                            {t('Amount')}
                                        </th>
                                        <th className="py-2 text-start font-medium">
                                            {t('Reference')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transactions.length === 0 && (
                                        <tr>
                                            <td
                                                className="py-3 text-zinc-500"
                                                colSpan={5}
                                            >
                                                {t('No bank transactions yet.')}
                                            </td>
                                        </tr>
                                    )}
                                    {transactions.map((entry) => (
                                        <tr
                                            key={entry.id}
                                            className="border-b border-zinc-100"
                                        >
                                            <td className="py-2 pe-3 whitespace-nowrap">
                                                {dateTime(entry.at)}
                                            </td>
                                            <td className="py-2 pe-3">
                                                {t(
                                                    entry.type ===
                                                        'disbursement'
                                                        ? 'Payout'
                                                        : 'Direct debit',
                                                )}
                                            </td>
                                            <td className="py-2 pe-3">
                                                {entry.status === 'succeeded'
                                                    ? t('Succeeded')
                                                    : `${t('Failed')}: ${entry.failure_reason ?? ''}`}
                                            </td>
                                            <td className="py-2 pe-3 text-end tabular-nums">
                                                {money(entry.amount)}
                                            </td>
                                            <td
                                                className="py-2 font-mono text-xs"
                                                dir="ltr"
                                            >
                                                {entry.reference ?? '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </Section>

                        {/* 6. Audit trail */}
                        <Section number={6} title={t('Audit trail')}>
                            <ol className="space-y-2.5">
                                {timeline.map((entry) => {
                                    const key = entry.action.replace(
                                        'advance.',
                                        '',
                                    ) as AdvanceStatus;
                                    const label = advanceStatusMeta[key]?.label;

                                    return (
                                        <li
                                            key={entry.id}
                                            className="flex items-baseline gap-3 text-sm"
                                        >
                                            <span className="min-w-36 text-xs whitespace-nowrap text-zinc-500">
                                                {entry.at
                                                    ? dateTime(entry.at)
                                                    : '—'}
                                            </span>
                                            <span className="font-medium text-zinc-900">
                                                {label
                                                    ? t(label)
                                                    : entry.action}
                                            </span>
                                            <span className="text-xs text-zinc-500">
                                                {entry.actor ?? t('System')}
                                            </span>
                                        </li>
                                    );
                                })}
                            </ol>
                            <p className="mt-3 text-xs text-zinc-500">
                                {t(
                                    'Every status change is recorded and cannot be altered.',
                                )}
                            </p>
                        </Section>

                        {/* 7. Compliance & security */}
                        <Section number={7} title={t('Compliance & security')}>
                            <ul className="space-y-2.5">
                                {compliancePoints.map(([head, body]) => (
                                    <li key={head} className="flex gap-2.5">
                                        <ShieldCheck className="mt-0.5 size-4 shrink-0 text-brand-soft-foreground" />
                                        <p className="text-sm text-zinc-700">
                                            <span className="font-semibold text-zinc-900">
                                                {head}.
                                            </span>{' '}
                                            {body}
                                        </p>
                                    </li>
                                ))}
                            </ul>

                            <div className="mt-6 grid grid-cols-2 gap-8 border-t border-dashed border-zinc-300 pt-6">
                                <div>
                                    <div className="h-8 border-b border-zinc-400" />
                                    <p className="mt-1 text-xs text-zinc-500">
                                        {t('Reviewed by')}
                                    </p>
                                </div>
                                <div>
                                    <div className="h-8 border-b border-zinc-400" />
                                    <p className="mt-1 text-xs text-zinc-500">
                                        {t('Date')}
                                    </p>
                                </div>
                            </div>
                        </Section>
                    </article>
                </div>
            </div>
        </>
    );
}

function PartyCard({
    role,
    name,
    detail,
}: {
    role: string;
    name: string;
    detail: string;
}) {
    return (
        <div className="rounded-lg border border-zinc-200 bg-zinc-50/60 p-3">
            <p className="text-[11px] font-medium tracking-wide text-zinc-500 uppercase">
                {role}
            </p>
            <p className="mt-0.5 font-semibold text-zinc-900">{name}</p>
            <p className="text-xs text-zinc-500">{detail}</p>
        </div>
    );
}
