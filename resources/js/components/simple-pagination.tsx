import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { useT } from '@/lib/i18n';
import type { LaravelPaginator, Paginated } from '@/types';

/**
 * Previous / next links for either a resource collection or a plain Laravel paginator.
 */
export function SimplePagination<T>({
    page,
}: {
    page: Paginated<T> | LaravelPaginator<T>;
}) {
    const t = useT();
    const { current, last, prev, next } =
        'meta' in page
            ? {
                  current: page.meta.current_page,
                  last: page.meta.last_page,
                  prev: page.links.prev,
                  next: page.links.next,
              }
            : {
                  current: page.current_page,
                  last: page.last_page,
                  prev: page.prev_page_url,
                  next: page.next_page_url,
              };

    if (last <= 1) {
        return null;
    }

    return (
        <nav className="flex items-center justify-between gap-2 text-sm">
            <PageLink href={prev} label={t('Previous')} />
            <span className="text-muted-foreground">
                {t('Page :current of :last', { current, last })}
            </span>
            <PageLink href={next} label={t('Next')} />
        </nav>
    );
}

function PageLink({ href, label }: { href: string | null; label: string }) {
    if (href === null) {
        return (
            <Button variant="outline" size="sm" disabled>
                {label}
            </Button>
        );
    }

    return (
        <Button variant="outline" size="sm" asChild>
            <Link href={href} preserveScroll>
                {label}
            </Link>
        </Button>
    );
}
