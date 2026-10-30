import { Head, Link } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowRight,
    CheckCircle2,
    TriangleAlert,
} from 'lucide-react';
import { PageHeader } from '@/components/portal/page-header';
import { TermsForm } from '@/components/portal/terms-form';
import type { TermsValues } from '@/components/portal/terms-form';
import { Button } from '@/components/ui/button';
import { useState } from 'react';
import { tKey, useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';

type Props = {
    terms: {
        auto_approve: boolean;
        disbursement_sla_hours: number;
        collection_offset_days: number;
        collection_max_attempts: number;
        collection_retry_days: number;
        cutoff_days_before_payday: number;
        limit_ratio_bps: number;
        flat_fee: number;
        max_draws_per_cycle: number;
        min_tenure_months: number;
    };
    counts: {
        requested: number;
        disbursement_failed: number;
        collection_failed: number;
        overdue: number;
        disbursed: number;
    };
    disclosure: { version: string; published_at: string | null } | null;
    drivers: Record<'bank' | 'funder' | 'payroll', string>;
    employerOverrides: number;
    editableTerms: TermsValues;
};

type HealthRow = {
    label: string;
    value: string;
    ok: boolean;
};

export default function Workflow({
    terms,
    counts,
    disclosure,
    drivers,
    employerOverrides,
    editableTerms,
}: Props) {
    const t = useT();
    const money = useMoney();
    const [isEditingRules, setIsEditingRules] = useState(false);

    const simulated = Object.values(drivers).some(
        (driver) => driver === 'fake',
    );

    const healthRows: HealthRow[] = [
        {
            label: t('Current disclosure'),
            value: disclosure
                ? t('Version :v', { v: disclosure.version })
                : t('No published disclosure'),
            ok: disclosure !== null,
        },
        {
            label: t('Advances awaiting approval'),
            value: String(counts.requested),
            ok: counts.requested === 0,
        },
        {
            label: t('Collection failures'),
            value: String(counts.collection_failed),
            ok: counts.collection_failed === 0,
        },
        {
            label: t('Overdue advances'),
            value: String(counts.overdue),
            ok: counts.overdue === 0,
        },
        {
            label: t('Money rails'),
            value: simulated ? t('Simulated (prototype)') : t('Live'),
            ok: !simulated,
        },
        {
            label: t('Per-employer overrides'),
            value: t(':n employers override the platform terms', {
                n: employerOverrides,
            }),
            ok: true,
        },
    ];

    const rules: [string, string][] = [
        [
            t('Limit'),
            t(':percent% of salary', {
                percent: terms.limit_ratio_bps / 100,
            }),
        ],
        [t('Service fee'), money(terms.flat_fee)],
        [
            t('Minimum tenure'),
            t(':count months', { count: terms.min_tenure_months }),
        ],
        [
            t('Cutoff'),
            t(':count days before payday', {
                count: terms.cutoff_days_before_payday,
            }),
        ],
        [t('Draws per cycle'), String(terms.max_draws_per_cycle)],
    ];

    const steps: {
        name: string;
        actor: string;
        rules: string[];
        branch?: string;
    }[] = [
        {
            name: t('Request'),
            actor: t('Employee'),
            rules: [t('Employee requests an advance against verified salary.')],
        },
        {
            name: t('Approve'),
            actor: t('Platform'),
            rules: [terms.auto_approve ? t('Automatic') : t('Manual review')],
        },
        {
            name: t('Disburse'),
            actor: t('Bank'),
            rules: [
                t('Funded within :hours h', {
                    hours: terms.disbursement_sla_hours,
                }),
            ],
        },
        {
            name: t('Collect'),
            actor: t('Bank'),
            rules: [
                t('On payday + :days days', {
                    days: terms.collection_offset_days,
                }),
                t('Up to :n attempts, :gap days apart', {
                    n: terms.collection_max_attempts,
                    gap: terms.collection_retry_days,
                }),
            ],
            branch: t(
                'If a collection fails: retry, then overdue if every attempt fails.',
            ),
        },
        {
            name: t('Repaid'),
            actor: t('Employee'),
            rules: [t('Advance closed in full.')],
        },
    ];

    return (
        <>
            <Head title={t('Workflow')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Workflow')}
                    description={t(
                        'How an advance moves from request to repayment, and the rules in force.',
                    )}
                />

                <section className="space-y-3">
                    <h2 className="font-medium">{t('Steps')}</h2>
                    <ol className="flex flex-col gap-3 lg:flex-row lg:items-stretch">
                        {steps.map((step, index) => (
                            <li
                                key={step.name}
                                className="flex flex-col gap-3 lg:flex-1 lg:flex-row"
                            >
                                <div className="flex-1 space-y-3 rounded-xl border bg-card p-4">
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-soft text-sm font-semibold text-brand-soft-foreground">
                                            {index + 1}
                                        </span>
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {step.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {step.actor}
                                            </p>
                                        </div>
                                    </div>
                                    <ul className="space-y-1 text-sm text-muted-foreground">
                                        {step.rules.map((rule) => (
                                            <li key={rule}>{rule}</li>
                                        ))}
                                    </ul>
                                    {step.branch && (
                                        <p className="rounded-md bg-brand-soft px-2 py-1.5 text-xs text-brand-soft-foreground">
                                            {step.branch}
                                        </p>
                                    )}
                                </div>
                                {index < steps.length - 1 && (
                                    <span
                                        aria-hidden
                                        className="flex items-center justify-center text-muted-foreground"
                                    >
                                        <ArrowDown className="size-5 lg:hidden" />
                                        <ArrowRight className="hidden size-5 lg:block rtl:rotate-180" />
                                    </span>
                                )}
                            </li>
                        ))}
                    </ol>
                </section>

                <section className="space-y-3">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h2 className="font-medium">{t('Rules in force')}</h2>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            aria-expanded={isEditingRules}
                            onClick={() => setIsEditingRules(!isEditingRules)}
                        >
                            {t('Edit rules')}
                        </Button>
                    </div>
                    <dl className="grid gap-3 rounded-xl border bg-card p-4 sm:grid-cols-2 lg:grid-cols-5">
                        {rules.map(([label, value]) => (
                            <div key={label} className="space-y-1">
                                <dt className="text-xs text-muted-foreground">
                                    {label}
                                </dt>
                                <dd className="font-medium tabular-nums">
                                    {value}
                                </dd>
                            </div>
                        ))}
                    </dl>

                    {isEditingRules && (
                        <div className="space-y-4 rounded-xl border bg-card p-4">
                            <TermsForm
                                action={admin.terms.update.form()}
                                values={editableTerms}
                                submitLabel={t('Save platform terms')}
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => setIsEditingRules(false)}
                            >
                                {t('Cancel')}
                            </Button>
                        </div>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="font-medium">{t('Health check')}</h2>
                    <ul className="divide-y rounded-xl border bg-card">
                        {healthRows.map((row) => (
                            <li
                                key={row.label}
                                className="flex items-start gap-3 px-4 py-3 text-sm"
                            >
                                <HealthIcon ok={row.ok} />
                                <span className="font-medium">{row.label}</span>
                                <span
                                    className={cn(
                                        'ms-auto text-end tabular-nums',
                                        row.ok
                                            ? 'text-muted-foreground'
                                            : 'text-amber-700',
                                    )}
                                >
                                    {row.value}
                                </span>
                            </li>
                        ))}
                    </ul>
                </section>

                <div className="flex flex-wrap gap-2">
                    <Button asChild variant="outline">
                        <Link href={admin.terms.edit()}>
                            {t('Edit program terms')}
                        </Link>
                    </Button>
                    <Button asChild variant="outline">
                        <Link href={admin.advances.index()}>
                            {t('View advances')}
                        </Link>
                    </Button>
                </div>
            </div>
        </>
    );
}

function HealthIcon({ ok }: { ok: boolean }) {
    return ok ? (
        <CheckCircle2
            aria-hidden
            className="mt-0.5 size-4 shrink-0 text-emerald-600"
        />
    ) : (
        <TriangleAlert
            aria-hidden
            className="mt-0.5 size-4 shrink-0 text-amber-600"
        />
    );
}

Workflow.layout = {
    breadcrumbs: [{ title: tKey('Workflow'), href: admin.workflow.show() }],
};
