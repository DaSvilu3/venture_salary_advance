import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { AdvanceStatusBadge } from '@/components/advances/advance-status-badge';
import { useDates } from '@/lib/format';
import { useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { show } from '@/routes/app/advances';
import type { Advance } from '@/types';

export function AdvanceListItem({ advance }: { advance: Advance }) {
    const t = useT();
    const money = useMoney();
    const { date } = useDates();

    return (
        <Link
            href={show(advance.reference)}
            prefetch
            className="flex items-center gap-3 rounded-xl border bg-card p-4 transition-colors hover:bg-accent"
        >
            <div className="min-w-0 flex-1 space-y-1">
                <div className="flex items-center gap-2">
                    <span className="font-semibold tabular-nums">
                        {money(advance.amount)}
                    </span>
                    <AdvanceStatusBadge status={advance.status} />
                </div>
                <p className="text-xs text-muted-foreground">
                    {t('Requested :date · due :due', {
                        date: date(advance.requested_at),
                        due: date(advance.due_date),
                    })}
                </p>
            </div>
            <ChevronRight className="size-4 text-muted-foreground rtl:rotate-180" />
        </Link>
    );
}
