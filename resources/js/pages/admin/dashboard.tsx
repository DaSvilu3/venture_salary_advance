import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowDownToLine,
    ArrowUpFromLine,
    Building2,
    Coins,
    Percent,
    Users,
    Wallet,
} from 'lucide-react';
import { AdvanceTable } from '@/components/advances/advance-table';
import { PageHeader } from '@/components/portal/page-header';
import { StatCard } from '@/components/portal/stat-card';
import { Button } from '@/components/ui/button';
import { tKey, useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { dashboard } from '@/routes/admin';
import { index as advancesIndex, show } from '@/routes/admin/advances';
import type { Advance } from '@/types';

type Props = {
    book: {
        outstanding: number;
        outstanding_count: number;
        overdue: number;
        overdue_count: number;
    };
    month: {
        label: string;
        disbursed: number;
        collected: number;
        fees: number;
        collection_failure_rate: number;
    };
    network: {
        employers_active: number;
        employers_pending: number;
        employees_enrolled: number;
    };
    needsAttention: { data: Advance[] };
};

export default function Dashboard({
    book,
    month,
    network,
    needsAttention,
}: Props) {
    const t = useT();
    const money = useMoney();

    return (
        <>
            <Head title={t('Platform dashboard')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Platform dashboard')}
                    description={t('The advance book across all employers.')}
                />

                <section className="space-y-3">
                    <h2 className="text-sm font-medium text-muted-foreground">
                        {t('Book')}
                    </h2>
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
                            tone={book.overdue_count > 0 ? 'danger' : 'default'}
                            hint={t(':count advances', {
                                count: book.overdue_count,
                            })}
                        />
                        <StatCard
                            label={t('Active employers')}
                            value={network.employers_active}
                            icon={Building2}
                            hint={t(':count waiting to go live', {
                                count: network.employers_pending,
                            })}
                        />
                        <StatCard
                            label={t('Enrolled employees')}
                            value={network.employees_enrolled}
                            icon={Users}
                        />
                    </div>
                </section>

                <section className="space-y-3">
                    <h2 className="text-sm font-medium text-muted-foreground">
                        {t('This month')}
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard
                            label={t('Paid out')}
                            value={money(month.disbursed)}
                            icon={ArrowUpFromLine}
                        />
                        <StatCard
                            label={t('Collected')}
                            value={money(month.collected)}
                            icon={ArrowDownToLine}
                        />
                        <StatCard
                            label={t('Fees earned')}
                            value={money(month.fees)}
                            icon={Coins}
                        />
                        <StatCard
                            label={t('Failed collection attempts')}
                            value={`${month.collection_failure_rate}%`}
                            icon={Percent}
                            tone={
                                month.collection_failure_rate > 10
                                    ? 'warning'
                                    : 'default'
                            }
                        />
                    </div>
                </section>

                <section className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h2 className="font-medium">{t('Needs attention')}</h2>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={advancesIndex()}>
                                {t('All advances')}
                            </Link>
                        </Button>
                    </div>
                    <AdvanceTable
                        advances={needsAttention.data}
                        href={(advance) => show.url(advance.reference)}
                    />
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: tKey('Dashboard'), href: dashboard() }],
};
