import { Head, Link } from '@inertiajs/react';
import {
    BadgeCheck,
    CalendarDays,
    CircleAlert,
    HandCoins,
    UserPlus,
    Users,
} from 'lucide-react';
import { PageHeader } from '@/components/portal/page-header';
import { StatCard } from '@/components/portal/stat-card';
import { Button } from '@/components/ui/button';
import { useDates } from '@/lib/format';
import { tKey, useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { dashboard } from '@/routes/employer';
import { index as employeesIndex, show } from '@/routes/employer/employees';

type Props = {
    employer: { name: string; status: string };
    payday: string;
    enrolment: Record<string, number>;
    cycle: { drawn: number; advances: number; employees_using: number };
    collections: { outstanding: number; employees: number; overdue: number };
    pendingApprovals: {
        id: number;
        name: string;
        staff_no: string;
        salary_verified: boolean;
    }[];
};

export default function Dashboard({
    employer,
    payday,
    enrolment,
    cycle,
    collections,
    pendingApprovals,
}: Props) {
    const t = useT();
    const money = useMoney();
    const { date } = useDates();
    const enrolled = enrolment.enrolled ?? 0;
    const usage =
        enrolled > 0 ? Math.round((cycle.employees_using / enrolled) * 100) : 0;

    return (
        <>
            <Head title={t('Employer dashboard')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={employer.name}
                    description={t(
                        "Your staff's enrolment and usage at a glance.",
                    )}
                    actions={
                        <Button asChild>
                            <Link href={employeesIndex()}>
                                <UserPlus />
                                {t('Manage employees')}
                            </Link>
                        </Button>
                    }
                />

                {employer.status !== 'active' && (
                    <p className="flex items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
                        <CircleAlert className="size-4 shrink-0" />
                        {t(
                            'Your company is not active yet. Staff can onboard, but advances open once the platform team activates you.',
                        )}
                    </p>
                )}

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        label={t('Enrolled staff')}
                        value={enrolled}
                        icon={Users}
                        hint={t(
                            ':invited invited · :pending waiting for approval',
                            {
                                invited: enrolment.invited ?? 0,
                                pending: enrolment.pending_approval ?? 0,
                            },
                        )}
                    />
                    <StatCard
                        label={t('Using advances this cycle')}
                        value={`${usage}%`}
                        icon={BadgeCheck}
                        hint={t(':count of :total enrolled staff', {
                            count: cycle.employees_using,
                            total: enrolled,
                        })}
                    />
                    <StatCard
                        label={t('Drawn this cycle')}
                        value={money(cycle.drawn)}
                        icon={HandCoins}
                        hint={t(':count advances', { count: cycle.advances })}
                    />
                    <StatCard
                        label={t('To be collected')}
                        value={money(collections.outstanding)}
                        icon={CalendarDays}
                        tone={collections.overdue > 0 ? 'warning' : 'default'}
                        hint={
                            collections.overdue > 0
                                ? t(':count overdue', {
                                      count: collections.overdue,
                                  })
                                : t('By direct debit on :date', {
                                      date: date(payday),
                                  })
                        }
                    />
                </div>

                <p className="text-xs text-muted-foreground">
                    {t(
                        'Individual advances are private to each employee. Collections are taken from their own bank accounts, not from payroll.',
                    )}
                </p>

                <section className="space-y-3">
                    <h2 className="font-medium">
                        {t('Waiting for your approval')}
                    </h2>
                    {pendingApprovals.length === 0 ? (
                        <p className="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                            {t('Nobody is waiting for approval.')}
                        </p>
                    ) : (
                        <ul className="divide-y rounded-xl border bg-card">
                            {pendingApprovals.map((employee) => (
                                <li
                                    key={employee.id}
                                    className="flex items-center justify-between gap-3 px-4 py-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {employee.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {employee.staff_no}
                                            {!employee.salary_verified &&
                                                ` · ${t('Salary not matched with payroll')}`}
                                        </p>
                                    </div>
                                    <Button asChild size="sm" variant="outline">
                                        <Link href={show(employee.id)}>
                                            {t('Review')}
                                        </Link>
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: tKey('Dashboard'), href: dashboard() }],
};
