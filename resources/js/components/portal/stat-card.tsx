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
        <div className="rounded-2xl border bg-card p-5">
            <div className="flex items-center justify-between gap-2">
                <p className="text-sm text-muted-foreground">{label}</p>
                {Icon && (
                    <span className="flex size-9 items-center justify-center rounded-full bg-brand-soft text-brand-soft-foreground">
                        <Icon className="size-4" />
                    </span>
                )}
            </div>
            <p
                className={cn(
                    'mt-2 text-2xl font-semibold tracking-tight tabular-nums',
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
