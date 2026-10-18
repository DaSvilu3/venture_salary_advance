import type { StatusTone } from '@/lib/advance-status';
import { tKey } from '@/lib/i18n';

export const enrolmentStatusMeta: Record<
    string,
    { label: string; tone: StatusTone }
> = {
    invited: { label: tKey('Invited'), tone: 'neutral' },
    pending_approval: { label: tKey('Waiting for approval'), tone: 'warning' },
    enrolled: { label: tKey('Enrolled'), tone: 'success' },
    rejected: { label: tKey('Rejected'), tone: 'danger' },
    suspended: { label: tKey('Suspended'), tone: 'danger' },
};

export const employmentStatusMeta: Record<
    string,
    { label: string; tone: StatusTone }
> = {
    active: { label: tKey('Active'), tone: 'success' },
    on_notice: { label: tKey('On notice'), tone: 'warning' },
    terminated: { label: tKey('Left the company'), tone: 'neutral' },
};

export const employerStatusMeta: Record<
    string,
    { label: string; tone: StatusTone }
> = {
    pending: { label: tKey('Pending'), tone: 'warning' },
    active: { label: tKey('Active'), tone: 'success' },
    suspended: { label: tKey('Suspended'), tone: 'danger' },
};
