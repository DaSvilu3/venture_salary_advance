import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useEffect } from 'react';
import { AdvanceStatusBadge } from '@/components/advances/advance-status-badge';
import { timelineLabels } from '@/lib/advance-timeline';
import { useDates } from '@/lib/format';
import { useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { cn } from '@/lib/utils';
import { index } from '@/routes/app/advances';
import type { Advance } from '@/types';

type Props = {
    advance: Advance;
    timeline: { id: number; status: string; at: string | null }[];
    transactions: {
        id: number;
        type: string;
        status: string;
        amount: number;
        reference: string | null;
        failure_reason: string | null;
        at: string;
    }[];
};

const PAYOUT_IN_FLIGHT = ['requested', 'approved', 'disbursing'];

export default function ShowAdvance({ advance, timeline }: Props) {
    const t = useT();
    const money = useMoney();
    const { date, dateTime } = useDates();
    const payoutInFlight = PAYOUT_IN_FLIGHT.includes(advance.status);

    useEffect(() => {
        if (!payoutInFlight) {
            return;
        }

        const timer = window.setInterval(
            () =>
                router.reload({
                    only: ['advance', 'timeline', 'transactions'],
                }),
            2000,
        );

        return () => window.clearInterval(timer);
    }, [payoutInFlight]);

    return (
        <>
            <Head title={advance.reference} />

            <Link
                href={index()}
                className="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground"
            >
                <ArrowLeft className="size-4 rtl:rotate-180" />
                {t('Advances')}
            </Link>

            <section className="rounded-2xl border bg-card p-5">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="text-xs text-muted-foreground" dir="ltr">
                            {advance.reference}
                        </p>
                        <p className="mt-1 text-3xl font-semibold tabular-nums">
                            {money(advance.amount)}
                        </p>
                    </div>
                    <AdvanceStatusBadge status={advance.status} />
                </div>

                <dl className="mt-5 grid grid-cols-2 gap-4 text-sm">
                    <Detail
                        label={t('Service fee')}
                        value={money(advance.fee)}
                    />
                    <Detail
                        label={t('Total to repay')}
                        value={money(advance.total_due)}
                    />
                    <Detail
                        label={t('Due on')}
                        value={date(advance.due_date)}
                    />
                    <Detail
                        label={t('Requested on')}
                        value={dateTime(advance.requested_at)}
                    />
                </dl>

                {advance.failure_reason &&
                    [
                        'collection_failed',
                        'overdue',
                        'disbursement_failed',
                    ].includes(advance.status) && (
                        <p className="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-950 dark:bg-amber-950/40 dark:text-amber-100">
                            {advance.next_collection_attempt_on
                                ? t(
                                      'The last collection failed. We will try again on :date. Please keep :amount in your account.',
                                      {
                                          date: date(
                                              advance.next_collection_attempt_on,
                                          ),
                                          amount: money(advance.total_due),
                                      },
                                  )
                                : t(
                                      'This advance could not be completed. Our team will contact you.',
                                  )}
                        </p>
                    )}
            </section>

            <section className="mt-6">
                <h2 className="mb-3 text-sm font-medium text-muted-foreground">
                    {t('Timeline')}
                </h2>
                <ol className="relative space-y-4 border-s ps-5">
                    {timeline.map((event, position) => (
                        <li key={event.id} className="relative">
                            <span
                                className={cn(
                                    'absolute -start-[1.6rem] top-1 size-3 rounded-full border-2 border-background',
                                    position === timeline.length - 1
                                        ? 'bg-primary'
                                        : 'bg-muted-foreground/40',
                                )}
                            />
                            <p className="text-sm font-medium">
                                {t(
                                    timelineLabels[event.status] ??
                                        event.status,
                                )}
                            </p>
                            {event.at && (
                                <p className="text-xs text-muted-foreground">
                                    {dateTime(event.at)}
                                </p>
                            )}
                        </li>
                    ))}
                </ol>
            </section>
        </>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="mt-0.5 font-medium tabular-nums">{value}</dd>
        </div>
    );
}
