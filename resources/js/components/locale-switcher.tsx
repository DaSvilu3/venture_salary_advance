import { router } from '@inertiajs/react';
import { Languages } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { useLocale } from '@/lib/i18n';
import { update } from '@/routes/locale';

/**
 * Toggles between English and Arabic. A full reload follows so the document
 * direction and the once-loaded translations both refresh.
 */
export function LocaleSwitcher({ className }: { className?: string }) {
    const { locale } = useLocale();
    const nextLocale = locale === 'ar' ? 'en' : 'ar';

    const switchLocale = () => {
        router.put(
            update.url(),
            { locale: nextLocale },
            { onSuccess: () => window.location.reload() },
        );
    };

    return (
        <Button
            variant="ghost"
            size="sm"
            className={cn('gap-1.5', className)}
            onClick={switchLocale}
            data-test="locale-switcher"
        >
            <Languages className="size-4" />
            <span lang={nextLocale}>
                {nextLocale === 'ar' ? 'العربية' : 'English'}
            </span>
        </Button>
    );
}
