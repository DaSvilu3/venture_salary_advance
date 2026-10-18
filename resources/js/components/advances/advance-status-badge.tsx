import { advanceStatusMeta, statusToneClasses } from '@/lib/advance-status';
import { useT } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { AdvanceStatus } from '@/types';

export function AdvanceStatusBadge({
    status,
    className,
}: {
    status: AdvanceStatus;
    className?: string;
}) {
    const t = useT();
    const meta = advanceStatusMeta[status];

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                statusToneClasses[meta.tone],
                className,
            )}
        >
            {t(meta.label)}
        </span>
    );
}
