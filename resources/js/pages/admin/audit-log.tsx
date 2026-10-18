import { Head, router } from '@inertiajs/react';
import { PageHeader } from '@/components/portal/page-header';
import { EmptyRow, TD, TH, THead, TR, Table } from '@/components/portal/table';
import { SimplePagination } from '@/components/simple-pagination';
import { Button } from '@/components/ui/button';
import { useDates } from '@/lib/format';
import { tKey, useT } from '@/lib/i18n';
import { index } from '@/routes/admin/audit-log';
import type { LaravelPaginator } from '@/types';

type Log = {
    id: number;
    action: string;
    actor: string | null;
    subject: string | null;
    meta: Record<string, unknown> | null;
    at: string | null;
};

const PREFIXES = [
    '',
    'advance.',
    'employee.',
    'employer.',
    'terms.',
    'disclosure.',
    'simulation.',
    'user.',
];

export default function AuditLog({
    logs,
    filters,
}: {
    logs: LaravelPaginator<Log>;
    filters: { action: string };
}) {
    const t = useT();
    const { dateTime } = useDates();

    return (
        <>
            <Head title={t('Audit log')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Audit log')}
                    description={t(
                        'Every change to advances, staff, terms and settings, newest first.',
                    )}
                />

                <div className="flex flex-wrap gap-1">
                    {PREFIXES.map((prefix) => (
                        <Button
                            key={prefix || 'all'}
                            size="sm"
                            variant={
                                filters.action === prefix
                                    ? 'secondary'
                                    : 'ghost'
                            }
                            onClick={() =>
                                router.get(
                                    index.url(),
                                    prefix ? { action: prefix } : {},
                                    { preserveState: true },
                                )
                            }
                        >
                            {prefix === '' ? t('All') : prefix.replace('.', '')}
                        </Button>
                    ))}
                </div>

                <Table>
                    <THead>
                        <tr>
                            <TH>{t('When')}</TH>
                            <TH>{t('Action')}</TH>
                            <TH>{t('Record')}</TH>
                            <TH>{t('By')}</TH>
                            <TH>{t('Details')}</TH>
                        </tr>
                    </THead>
                    <tbody>
                        {logs.data.length === 0 && (
                            <EmptyRow colSpan={5}>
                                {t('Nothing recorded yet.')}
                            </EmptyRow>
                        )}
                        {logs.data.map((log) => (
                            <TR key={log.id}>
                                <TD className="whitespace-nowrap">
                                    {log.at ? dateTime(log.at) : '—'}
                                </TD>
                                <TD className="font-mono text-xs">
                                    {log.action}
                                </TD>
                                <TD className="text-xs text-muted-foreground">
                                    {log.subject ?? '—'}
                                </TD>
                                <TD>{log.actor ?? t('System')}</TD>
                                <TD
                                    className="max-w-md truncate font-mono text-xs text-muted-foreground"
                                    dir="ltr"
                                >
                                    {log.meta ? JSON.stringify(log.meta) : ''}
                                </TD>
                            </TR>
                        ))}
                    </tbody>
                </Table>

                <SimplePagination page={logs} />
            </div>
        </>
    );
}

AuditLog.layout = {
    breadcrumbs: [{ title: tKey('Audit log'), href: index() }],
};
