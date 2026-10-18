import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function StatCard({
    label,
    value,
    hint,
    icon: Icon,
    tone = 'default',
}: {
    label: string;
    value: ReactNode;
    hint?: ReactNode;
    icon?: LucideIcon;
    tone?: 'default' | 'warning' | 'danger';
}) {
    return (
        <div className="rounded-xl border bg-card p-4">
            <div className="flex items-center justify-between gap-2">
                <p className="text-sm text-muted-foreground">{label}</p>
                {Icon && <Icon className="size-4 text-muted-foreground" />}
            </div>
            <p
                className={cn(
                    'mt-2 text-2xl font-semibold tabular-nums',
                    tone === 'warning' && 'text-amber-600 dark:text-amber-400',
                    tone === 'danger' && 'text-red-600 dark:text-red-400',
                )}
            >
                {value}
            </p>
            {hint && (
                <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
            )}
        </div>
    );
}
