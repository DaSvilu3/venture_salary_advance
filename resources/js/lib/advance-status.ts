import { tKey } from '@/lib/i18n';
import type { AdvanceStatus } from '@/types';

export type StatusTone =
    | 'neutral'
    | 'progress'
    | 'success'
    | 'warning'
    | 'danger';

/**
 * How each advance status is labelled for people, and its colour.
 */
export const advanceStatusMeta: Record<
    AdvanceStatus,
    { label: string; tone: StatusTone }
> = {
    requested: { label: tKey('Requested'), tone: 'progress' },
    approved: { label: tKey('Approved'), tone: 'progress' },
    disbursing: { label: tKey('Sending to your bank'), tone: 'progress' },
    disbursed: { label: tKey('Paid out'), tone: 'success' },
    collection_failed: { label: tKey('Collection failed'), tone: 'warning' },
    overdue: { label: tKey('Overdue'), tone: 'danger' },
    collected: { label: tKey('Repaid'), tone: 'neutral' },
    disbursement_failed: { label: tKey('Payout failed'), tone: 'danger' },
    rejected: { label: tKey('Rejected'), tone: 'danger' },
    cancelled: { label: tKey('Cancelled'), tone: 'neutral' },
    written_off: { label: tKey('Written off'), tone: 'danger' },
};

export const statusToneClasses: Record<StatusTone, string> = {
    neutral: 'bg-muted text-muted-foreground',
    progress: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
    success:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    warning:
        'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300',
    danger: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
};
