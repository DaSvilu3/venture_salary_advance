import { Link } from '@inertiajs/react';
import { AdvanceStatusBadge } from '@/components/advances/advance-status-badge';
import { EmptyRow, TD, TH, THead, TR, Table } from '@/components/portal/table';
import { useDates } from '@/lib/format';
import { useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import type { Advance } from '@/types';

/**
 * Advances across employers, for the admin and partner portals.
 */
export function AdvanceTable({
    advances,
    href,
}: {
    advances: Advance[];
    href?: (advance: Advance) => string;
}) {
    const t = useT();
    const money = useMoney();
    const { date } = useDates();

    return (
        <Table>
            <THead>
                <tr>
                    <TH>{t('Reference')}</TH>
                    <TH>{t('Employee')}</TH>
                    <TH>{t('Employer')}</TH>
                    <TH className="text-end">{t('Amount')}</TH>
                    <TH className="text-end">{t('Total due')}</TH>
                    <TH>{t('Due on')}</TH>
                    <TH>{t('Status')}</TH>
                </tr>
            </THead>
            <tbody>
                {advances.length === 0 && (
                    <EmptyRow colSpan={7}>{t('No advances match.')}</EmptyRow>
                )}
                {advances.map((advance) => (
                    <TR key={advance.id}>
                        <TD className="font-mono text-xs" dir="ltr">
                            {href ? (
                                <Link
                                    href={href(advance)}
                                    className="hover:underline"
                                >
                                    {advance.reference}
                                </Link>
                            ) : (
                                advance.reference
                            )}
                        </TD>
                        <TD>{advance.employee?.name ?? '—'}</TD>
                        <TD className="text-muted-foreground">
                            {advance.employer?.name ?? '—'}
                        </TD>
                        <TD className="text-end tabular-nums">
                            {money(advance.amount)}
                        </TD>
                        <TD className="text-end tabular-nums">
                            {money(advance.total_due)}
                        </TD>
                        <TD className="whitespace-nowrap">
                            {date(advance.due_date)}
                        </TD>
                        <TD>
                            <AdvanceStatusBadge status={advance.status} />
                        </TD>
                    </TR>
                ))}
            </tbody>
        </Table>
    );
}
