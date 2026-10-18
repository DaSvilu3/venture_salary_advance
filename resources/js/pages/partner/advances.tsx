import { Head, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { AdvanceTable } from '@/components/advances/advance-table';
import { PageHeader } from '@/components/portal/page-header';
import { SimplePagination } from '@/components/simple-pagination';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { advanceStatusMeta } from '@/lib/advance-status';
import { tKey, useT } from '@/lib/i18n';
import { exportMethod, index } from '@/routes/partner/advances';
import type { Advance, AdvanceStatus, Paginated } from '@/types';

type Filters = { status: string; employer: string };

type Props = {
    advances: Paginated<Advance>;
    filters: Filters;
    employers: { id: number; name: string }[];
    statuses: AdvanceStatus[];
};

const ALL = 'all';

export default function Advances({
    advances,
    filters,
    employers,
    statuses,
}: Props) {
    const t = useT();
    const activeFilters = Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    );

    const apply = (next: Partial<Filters>) => {
        const merged = { ...filters, ...next };
        router.get(
            index.url(),
            Object.fromEntries(
                Object.entries(merged).filter(([, value]) => value !== ''),
            ),
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    return (
        <>
            <Head title={t('Advances')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Advances')}
                    description={t(
                        'Read-only. Export the filtered list for reconciliation.',
                    )}
                    actions={
                        <Button asChild variant="outline">
                            <a
                                href={exportMethod.url({
                                    query: activeFilters,
                                })}
                            >
                                <Download />
                                {t('Export CSV')}
                            </a>
                        </Button>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <Select
                        value={filters.status || ALL}
                        onValueChange={(value) =>
                            apply({ status: value === ALL ? '' : value })
                        }
                    >
                        <SelectTrigger className="w-48">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>
                                {t('All statuses')}
                            </SelectItem>
                            {statuses.map((status) => (
                                <SelectItem key={status} value={status}>
                                    {t(advanceStatusMeta[status].label)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.employer || ALL}
                        onValueChange={(value) =>
                            apply({ employer: value === ALL ? '' : value })
                        }
                    >
                        <SelectTrigger className="w-56">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>
                                {t('All employers')}
                            </SelectItem>
                            {employers.map((employer) => (
                                <SelectItem
                                    key={employer.id}
                                    value={String(employer.id)}
                                >
                                    {employer.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <AdvanceTable advances={advances.data} />
                <SimplePagination page={advances} />
            </div>
        </>
    );
}

Advances.layout = {
    breadcrumbs: [{ title: tKey('Advances'), href: index() }],
};
