import { tKey } from '@/lib/i18n';

/**
 * What each audit-logged status change means to the person reading the timeline.
 */
export const timelineLabels: Record<string, string> = {
    requested: tKey('You requested the advance'),
    approved: tKey('Approved against your limit'),
    disbursing: tKey('Sent to the bank'),
    disbursed: tKey('Paid into your account'),
    disbursement_failed: tKey('The bank could not pay it out'),
    collection_failed: tKey('Payday collection failed'),
    overdue: tKey('Marked overdue'),
    collected: tKey('Repaid by direct debit'),
    cancelled: tKey('Cancelled'),
    rejected: tKey('Rejected'),
    written_off: tKey('Written off'),
};
