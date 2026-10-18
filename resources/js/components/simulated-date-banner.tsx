import { usePage } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import { useT } from '@/lib/i18n';

/**
 * Shown on every page while an admin has moved the demo clock away from the real date.
 */
export function SimulatedDateBanner() {
    const { clock } = usePage().props;
    const t = useT();

    if (!clock.isSimulated) {
        return null;
    }

    return (
        <div className="flex items-center justify-center gap-2 bg-amber-100 px-4 py-1.5 text-xs font-medium text-amber-900 dark:bg-amber-950 dark:text-amber-200">
            <CalendarClock className="size-3.5" />
            {t('Demo clock: today is simulated as :date', {
                date: clock.today,
            })}
        </div>
    );
}
