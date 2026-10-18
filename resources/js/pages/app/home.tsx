import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarDays, CircleAlert, Plus } from 'lucide-react';
import { AdvanceListItem } from '@/components/advances/advance-list-item';
import { Button } from '@/components/ui/button';
import { useDates } from '@/lib/format';
import { useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { create } from '@/routes/app/advances';
import type { Advance, Eligibility } from '@/types';

type Props = {
    employer: string;
    bank: { name: string; brand_color: string | null } | null;
    eligibility: Eligibility;
    activeAdvances: { data: Advance[] };
};

export default function Home({
    employer,
    bank,
    eligibility,
    activeAdvances,
}: Props) {
    const { auth } = usePage().props;
    const t = useT();
    const money = useMoney();
    const { date } = useDates();
    const usedPercent =
        eligibility.limit > 0
            ? Math.min(100, (eligibility.drawn / eligibility.limit) * 100)
            : 0;

    return (
        <>
            <Head title={t('Home')} />

            <div className="space-y-6">
                <div>
                    <p className="text-sm text-muted-foreground">{employer}</p>
                    <h1 className="text-xl font-semibold">
                        {t('Hello, :name', {
                            name: auth.user.name.split(' ')[0],
                        })}
                    </h1>
                </div>

                <section
                    className="overflow-hidden rounded-2xl bg-primary p-5 text-primary-foreground shadow-sm"
                    style={
                        bank?.brand_color
                            ? {
                                  backgroundColor: bank.brand_color,
                                  color: '#fff',
                              }
                            : undefined
                    }
                    aria-labelledby="available-heading"
                >
                    <p id="available-heading" className="text-sm opacity-80">
                        {t('Available now')}
                    </p>
                    <p
                        className="mt-1 text-3xl font-semibold tabular-nums"
                        data-test="available-amount"
                    >
                        {money(
                            eligibility.eligible ? eligibility.available : 0,
                        )}
                    </p>

                    <div className="mt-4 h-1.5 overflow-hidden rounded-full bg-white/25">
                        <div
                            className="h-full rounded-full bg-white"
                            style={{ width: `${usedPercent}%` }}
                        />
                    </div>
                    <p className="mt-2 text-xs opacity-80">
                        {t(':drawn used of your :limit limit this pay cycle', {
                            drawn: money(eligibility.drawn),
                            limit: money(eligibility.limit),
                        })}
                    </p>

                    <div className="mt-4 flex items-center gap-2 text-xs opacity-90">
                        <CalendarDays className="size-4" />
                        {t('Next payday :date', {
                            date: date(eligibility.payday),
                        })}
                    </div>

                    {eligibility.eligible && (
                        <Button
                            asChild
                            variant="secondary"
                            className="mt-5 w-full"
                            data-test="request-advance-button"
                        >
                            <Link href={create()}>
                                <Plus />
                                {t('Get an advance')}
                            </Link>
                        </Button>
                    )}
                </section>

                {!eligibility.eligible && (
                    <section className="space-y-2 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
                        {eligibility.reasons.map((reason) => (
                            <p key={reason.code} className="flex gap-2">
                                <CircleAlert className="mt-0.5 size-4 shrink-0" />
                                {reason.message}
                            </p>
                        ))}
                    </section>
                )}

                <section className="space-y-3">
                    <h2 className="text-sm font-medium text-muted-foreground">
                        {t('In progress')}
                    </h2>
                    {activeAdvances.data.length === 0 ? (
                        <p className="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                            {t('No advances in progress.')}
                        </p>
                    ) : (
                        activeAdvances.data.map((advance) => (
                            <AdvanceListItem
                                key={advance.id}
                                advance={advance}
                            />
                        ))
                    )}
                </section>

                {bank && (
                    <p className="text-center text-xs text-muted-foreground">
                        {t('Advances are paid and collected by :bank.', {
                            bank: bank.name,
                        })}
                    </p>
                )}
            </div>
        </>
    );
}
