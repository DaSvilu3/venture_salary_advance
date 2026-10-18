import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, FileX, Wallet } from 'lucide-react';
import { MonthlyColumns } from '@/components/charts/monthly-columns';
import { PageHeader } from '@/components/portal/page-header';
import { StatCard } from '@/components/portal/stat-card';
import { EmptyRow, TD, TH, THead, TR, Table } from '@/components/portal/table';
import { tKey, useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { dashboard } from '@/routes/partner';
import { index as advancesIndex } from '@/routes/partner/advances';

type Props = {
    partner: { name: string; type: 'bank' | 'funder' };
    book: {
        outstanding: number;
        outstanding_count: number;
        overdue: number;
        collected_total: number;
        written_off: number;
    };
    ageing: { '1_7': number; '8_30': number; '31_plus': number };
    exposure: {
        employer_id: number;
        employer: string;
        advances: number;
        outstanding: number;
        overdue: number;
    }[];
    trend: { month: string; disbursed: number; collected: number }[];
};

export default function Dashboard({
    partner,
    book,
    ageing,
    exposure,
    trend,
}: Props) {
    const t = useT();
    const money = useMoney();

    return (
        <>
            <Head title={t('Partner dashboard')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={partner.name}
                    description={
                        partner.type === 'bank'
                            ? t(
                                  'Advances you pay out and collect by direct debit.',
                              )
                            : t(
                                  'Advances you fund. Repayments are reported as they are collected.',
                              )
                    }
                />

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        label={t('Outstanding')}
                        value={money(book.outstanding)}
                        icon={Wallet}
                        hint={t(':count advances', {
                            count: book.outstanding_count,
                        })}
                    />
                    <StatCard
                        label={t('Overdue')}
                        value={money(book.overdue)}
                        icon={AlertTriangle}
                        tone={book.overdue > 0 ? 'danger' : 'default'}
                    />
                    <StatCard
                        label={t('Repaid to date')}
                        value={money(book.collected_total)}
                        icon={CheckCircle2}
                    />
                    <StatCard
                        label={t('Written off')}
                        value={money(book.written_off)}
                        icon={FileX}
                    />
                </div>

                <div className="grid gap-6 xl:grid-cols-[2fr_1fr]">
                    <section className="rounded-xl border bg-card p-4">
                        <MonthlyColumns
                            title={t('Paid out and collected by month')}
                            rows={trend}
                            series={[
                                {
                                    key: 'disbursed',
                                    label: t('Paid out'),
                                    color: 'var(--series-1)',
                                },
                                {
                                    key: 'collected',
                                    label: t('Collected'),
                                    color: 'var(--series-2)',
                                },
                            ]}
                        />
                    </section>

                    <section className="space-y-3 rounded-xl border bg-card p-4">
                        <h2 className="font-medium">
                            {t('Unpaid by days past due')}
                        </h2>
                        <dl className="divide-y text-sm">
                            {(
                                [
                                    ['1_7', t('1–7 days')],
                                    ['8_30', t('8–30 days')],
                                    ['31_plus', t('Over 30 days')],
                                ] as const
                            ).map(([bucket, label]) => (
                                <div
                                    key={bucket}
                                    className="flex items-center justify-between py-2"
                                >
                                    <dt className="text-muted-foreground">
                                        {label}
                                    </dt>
                                    <dd className="font-medium tabular-nums">
                                        {money(ageing[bucket])}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                        <p className="text-xs text-muted-foreground">
                            {t(
                                'Includes advances still being retried and those marked overdue.',
                            )}
                        </p>
                    </section>
                </div>

                <section className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h2 className="font-medium">
                            {t('Exposure by employer')}
                        </h2>
                        <Link
                            href={advancesIndex()}
                            className="text-sm text-primary hover:underline"
                        >
                            {t('All advances')}
                        </Link>
                    </div>
                    <Table>
                        <THead>
                            <tr>
                                <TH>{t('Employer')}</TH>
                                <TH className="text-end">{t('Advances')}</TH>
                                <TH className="text-end">{t('Outstanding')}</TH>
                                <TH className="text-end">{t('Overdue')}</TH>
                            </tr>
                        </THead>
                        <tbody>
                            {exposure.length === 0 && (
                                <EmptyRow colSpan={4}>
                                    {t('Nothing outstanding.')}
                                </EmptyRow>
                            )}
                            {exposure.map((row) => (
                                <TR key={row.employer_id}>
                                    <TD>
                                        <Link
                                            href={advancesIndex({
                                                query: {
                                                    employer: row.employer_id,
                                                },
                                            })}
                                            className="hover:underline"
                                        >
                                            {row.employer}
                                        </Link>
                                    </TD>
                                    <TD className="text-end tabular-nums">
                                        {row.advances}
                                    </TD>
                                    <TD className="text-end tabular-nums">
                                        {money(row.outstanding)}
                                    </TD>
                                    <TD className="text-end tabular-nums">
                                        {money(row.overdue)}
                                    </TD>
                                </TR>
                            ))}
                        </tbody>
                    </Table>
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: tKey('Dashboard'), href: dashboard() }],
};
