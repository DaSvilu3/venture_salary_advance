import { Head } from '@inertiajs/react';
import { AdvanceListItem } from '@/components/advances/advance-list-item';
import { SimplePagination } from '@/components/simple-pagination';
import { useT } from '@/lib/i18n';
import type { Advance, Paginated } from '@/types';

export default function Advances({
    advances,
}: {
    advances: Paginated<Advance>;
}) {
    const t = useT();

    return (
        <>
            <Head title={t('Advances')} />

            <h1 className="mb-4 text-xl font-semibold">{t('Advances')}</h1>

            {advances.data.length === 0 ? (
                <p className="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                    {t('You have not taken any advances yet.')}
                </p>
            ) : (
                <div className="space-y-3">
                    {advances.data.map((advance) => (
                        <AdvanceListItem key={advance.id} advance={advance} />
                    ))}
                    <SimplePagination page={advances} />
                </div>
            )}
        </>
    );
}
