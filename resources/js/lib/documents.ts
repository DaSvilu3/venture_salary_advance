import type { StatusTone } from '@/lib/advance-status';
import { tKey } from '@/lib/i18n';

/**
 * A company document as the employer and platform portals receive it.
 */
export type CompanyDocument = {
    id: number;
    type: string;
    original_name: string;
    status: string;
    review_note: string | null;
    size: number;
    uploaded_at: string | null;
    reviewed_at: string | null;
};

export const documentTypeMeta: Record<string, { label: string }> = {
    commercial_registration: { label: tKey('Commercial registration') },
    vat_certificate: { label: tKey('VAT certificate') },
    authorized_signatory: { label: tKey('Authorized signatory') },
    other: { label: tKey('Other') },
};

export const documentStatusMeta: Record<
    string,
    { label: string; tone: StatusTone }
> = {
    pending: { label: tKey('Pending'), tone: 'warning' },
    approved: { label: tKey('Approved'), tone: 'success' },
    rejected: { label: tKey('Rejected'), tone: 'danger' },
};
