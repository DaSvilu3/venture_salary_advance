import { Head, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AdvanceTable } from '@/components/advances/advance-table';
import { PageHeader } from '@/components/portal/page-header';
import { SimplePagination } from '@/components/simple-pagination';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { advanceStatusMeta } from '@/lib/advance-status';
import { tKey, useT } from '@/lib/i18n';
import { index, show } from '@/routes/admin/advances';
import type { Advance, AdvanceStatus, Paginated } from '@/types';

type Filters = { status: string; employer: string; search: string };

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
    const [search, setSearch] = useState(filters.search);

    const apply = (next: Partial<Filters>) => {
        const merged = { ...filters, search, ...next };
        router.get(
            index.url(),
            Object.fromEntries(
                Object.entries(merged).filter(([, value]) => value !== ''),
            ),
            { preserveState: true, replace: true },
        );
    };

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        apply({ search });
    };

    return (
        <>
            <Head title={t('Advances')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Advances')}
                    description={t('Every advance on the platform.')}
                />

                <div className="flex flex-wrap gap-2">
                    <form
                        onSubmit={submitSearch}
                        className="relative w-full max-w-xs"
                    >
                        <Search className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Reference or employee')}
                            className="ps-9"
                        />
                    </form>
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

                <AdvanceTable
                    advances={advances.data}
                    href={(advance) => show.url(advance.reference)}
                />
                <SimplePagination page={advances} />
            </div>
        </>
    );
}

Advances.layout = {
    breadcrumbs: [{ title: tKey('Advances'), href: index() }],
};
