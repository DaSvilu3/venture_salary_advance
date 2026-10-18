import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { useT } from '@/lib/i18n';
import type { Paginated } from '@/types';

export function SimplePagination<T>({ page }: { page: Paginated<T> }) {
    const t = useT();

    if (page.meta.last_page <= 1) {
        return null;
    }

    return (
        <nav className="flex items-center justify-between gap-2 text-sm">
            <Button
                variant="outline"
                size="sm"
                asChild={page.links.prev !== null}
                disabled={page.links.prev === null}
            >
                {page.links.prev ? (
                    <Link href={page.links.prev} preserveScroll>
                        {t('Previous')}
                    </Link>
                ) : (
                    <span>{t('Previous')}</span>
                )}
            </Button>
            <span className="text-muted-foreground">
                {t('Page :current of :last', {
                    current: page.meta.current_page,
                    last: page.meta.last_page,
                })}
            </span>
            <Button
                variant="outline"
                size="sm"
                asChild={page.links.next !== null}
                disabled={page.links.next === null}
            >
                {page.links.next ? (
                    <Link href={page.links.next} preserveScroll>
                        {t('Next')}
                    </Link>
                ) : (
                    <span>{t('Next')}</span>
                )}
            </Button>
        </nav>
    );
}
