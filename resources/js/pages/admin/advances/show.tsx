import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Ban, FileX, RefreshCw } from 'lucide-react';
import { AdvanceStatusBadge } from '@/components/advances/advance-status-badge';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/portal/page-header';
import { TD, TH, THead, TR, Table } from '@/components/portal/table';
import { Button } from '@/components/ui/button';
import { useDates } from '@/lib/format';
import { tKey, useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { cancel, collect, index, writeOff } from '@/routes/admin/advances';
import type { Advance } from '@/types';

type Props = {
    advance: Advance & {
        bank: string | null;
        funder: string | null;
        disclosure_version: string;
        mandate_reference: string | null;
    };
    allowedActions: {
        cancel: boolean;
        retry_collection: boolean;
        write_off: boolean;
    };
    timeline: {
        id: number;
        action: string;
        actor: string | null;
        meta: Record<string, unknown> | null;
        at: string | null;
    }[];
    transactions: {
        id: number;
        type: string;
        status: string;
        amount: number;
        provider: string;
        reference: string | null;
        failure_reason: string | null;
        at: string;
    }[];
};

export default function ShowAdvance({
    advance,
    allowedActions,
    timeline,
    transactions,
}: Props) {
    const t = useT();
    const money = useMoney();
    const { date, dateTime } = useDates();

    const details: [string, string][] = [
        [
            t('Employee'),
            `${advance.employee?.name ?? '—'} (${advance.employee?.staff_no ?? ''})`,
        ],
        [t('Employer'), advance.employer?.name ?? '—'],
        [t('Amount'), money(advance.amount)],
        [t('Service fee'), money(advance.fee)],
        [t('Total due'), money(advance.total_due)],
        [t('Due on'), date(advance.due_date)],
        [t('Bank partner'), advance.bank ?? '—'],
        [t('Funding partner'), advance.funder ?? '—'],
        [t('Mandate'), advance.mandate_reference ?? '—'],
        [t('Terms accepted'), advance.disclosure_version],
        [t('Collection attempts'), String(advance.collection_attempts)],
        [
            t('Next attempt'),
            advance.next_collection_attempt_on
                ? date(advance.next_collection_attempt_on)
                : '—',
        ],
    ];

    return (
        <>
            <Head title={advance.reference} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Link
                    href={index()}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground"
                >
                    <ArrowLeft className="size-4 rtl:rotate-180" />
                    {t('Advances')}
                </Link>

                <PageHeader
                    title={advance.reference}
                    description={advance.failure_reason ?? undefined}
                    actions={
                        <AdvanceStatusBadge
                            status={advance.status}
                            className="text-sm"
                        />
                    }
                />

                <div className="flex flex-wrap gap-2">
                    {allowedActions.retry_collection && (
                        <Form
                            {...collect.form(advance.reference)}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                >
                                    <RefreshCw />
                                    {t('Collect now')}
                                </Button>
                            )}
                        </Form>
                    )}
                    {allowedActions.cancel && (
                        <Form
                            {...cancel.form(advance.reference)}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                >
                                    <Ban />
                                    {t('Cancel advance')}
                                </Button>
                            )}
                        </Form>
                    )}
                    {allowedActions.write_off && (
                        <Form
                            {...writeOff.form(advance.reference)}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        <FileX />
                                        {t('Write off')}
                                    </Button>
                                    <InputError message={errors.advance} />
                                </>
                            )}
                        </Form>
                    )}
                </div>

                <div className="grid gap-6 xl:grid-cols-[1fr_1fr]">
                    <section className="rounded-xl border bg-card p-4">
                        <dl className="grid grid-cols-2 gap-4 text-sm">
                            {details.map(([label, value]) => (
                                <div key={label}>
                                    <dt className="text-xs text-muted-foreground">
                                        {label}
                                    </dt>
                                    <dd className="mt-0.5 font-medium">
                                        {value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </section>

                    <section className="rounded-xl border bg-card p-4">
                        <h2 className="mb-3 font-medium">{t('Audit trail')}</h2>
                        <ol className="space-y-3 text-sm">
                            {timeline.map((entry) => (
                                <li key={entry.id}>
                                    <p className="font-mono text-xs">
                                        {entry.action}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {[
                                            entry.at
                                                ? dateTime(entry.at)
                                                : null,
                                            entry.actor ?? t('System'),
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                    {entry.meta && (
                                        <p
                                            className="font-mono text-xs break-all text-muted-foreground"
                                            dir="ltr"
                                        >
                                            {JSON.stringify(entry.meta)}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ol>
                    </section>
                </div>

                <section className="space-y-3">
                    <h2 className="font-medium">{t('Bank transactions')}</h2>
                    <Table>
                        <THead>
                            <tr>
                                <TH>{t('When')}</TH>
                                <TH>{t('Type')}</TH>
                                <TH>{t('Result')}</TH>
                                <TH className="text-end">{t('Amount')}</TH>
                                <TH>{t('Reference')}</TH>
                            </tr>
                        </THead>
                        <tbody>
                            {transactions.map((entry) => (
                                <TR key={entry.id}>
                                    <TD className="whitespace-nowrap">
                                        {dateTime(entry.at)}
                                    </TD>
                                    <TD>
                                        {t(
                                            entry.type === 'disbursement'
                                                ? 'Payout'
                                                : 'Direct debit',
                                        )}
                                    </TD>
                                    <TD>
                                        {entry.status === 'succeeded'
                                            ? t('Succeeded')
                                            : `${t('Failed')}: ${entry.failure_reason ?? ''}`}
                                    </TD>
                                    <TD className="text-end tabular-nums">
                                        {money(entry.amount)}
                                    </TD>
                                    <TD className="font-mono text-xs" dir="ltr">
                                        {entry.reference ?? '—'}
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

ShowAdvance.layout = {
    breadcrumbs: [{ title: tKey('Advances'), href: index() }],
};
