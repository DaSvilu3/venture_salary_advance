import { statusToneClasses } from '@/lib/advance-status';
import type { StatusTone } from '@/lib/advance-status';
import { useT } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export function StatusPill({
    meta,
    className,
}: {
    meta: { label: string; tone: StatusTone } | undefined;
    className?: string;
}) {
    const t = useT();

    if (!meta) {
        return null;
    }

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
