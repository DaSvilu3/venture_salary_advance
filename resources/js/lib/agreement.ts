import type { StatusTone } from '@/lib/advance-status';
import { tKey } from '@/lib/i18n';

/**
 * The employer's decision on the programme agreement, as the portals receive it.
 */
export type AgreementStatus = 'pending' | 'accepted' | 'rejected';

export const agreementStatusMeta: Record<
    AgreementStatus,
    { label: string; tone: StatusTone }
> = {
    pending: { label: tKey('Pending'), tone: 'warning' },
    accepted: { label: tKey('Accepted'), tone: 'success' },
    rejected: { label: tKey('Rejected'), tone: 'danger' },
};
