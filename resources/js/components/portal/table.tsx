import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

export function Table({ className, ...props }: ComponentProps<'table'>) {
    return (
        <div className="overflow-x-auto rounded-2xl border bg-card">
            <table className={cn('w-full text-sm', className)} {...props} />
        </div>
    );
}

export function THead({ className, ...props }: ComponentProps<'thead'>) {
    return (
        <thead
            className={cn(
                'border-b bg-muted/40 text-xs text-muted-foreground',
                className,
            )}
            {...props}
        />
    );
}

export function TH({ className, ...props }: ComponentProps<'th'>) {
    return (
        <th
            className={cn(
                'px-4 py-2.5 text-start font-medium whitespace-nowrap',
                className,
            )}
            {...props}
        />
    );
}

export function TR({ className, ...props }: ComponentProps<'tr'>) {
    return (
        <tr
            className={cn(
                'border-b last:border-0 hover:bg-muted/30',
                className,
            )}
            {...props}
        />
    );
}

export function TD({ className, ...props }: ComponentProps<'td'>) {
    return (
        <td className={cn('px-4 py-3 align-middle', className)} {...props} />
    );
}

export function EmptyRow({
    colSpan,
    children,
}: {
    colSpan: number;
    children: React.ReactNode;
}) {
    return (
        <tr>
            <td
                colSpan={colSpan}
                className="px-4 py-10 text-center text-muted-foreground"
            >
                {children}
            </td>
        </tr>
    );
}
