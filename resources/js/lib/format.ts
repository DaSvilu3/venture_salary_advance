import { useLocale } from '@/lib/i18n';
import type { Locale } from '@/lib/i18n';

function intlLocale(locale: Locale): string {
    return locale === 'ar' ? 'ar-OM-u-nu-latn' : 'en-GB';
}

/**
 * "25 Oct 2026" / "٢٥ أكتوبر ٢٠٢٦" with Western digits. Plain dates are read
 * as calendar dates, not shifted by the viewer's time zone.
 */
export function formatDate(value: string, locale: Locale = 'en'): string {
    const date = /^\d{4}-\d{2}-\d{2}$/.test(value)
        ? new Date(`${value}T00:00:00`)
        : new Date(value);

    return new Intl.DateTimeFormat(intlLocale(locale), {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
}

export function formatDateTime(value: string, locale: Locale = 'en'): string {
    return new Intl.DateTimeFormat(intlLocale(locale), {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Asia/Muscat',
    }).format(new Date(value));
}

export function useDates(): {
    date: (value: string) => string;
    dateTime: (value: string) => string;
} {
    const { locale } = useLocale();

    return {
        date: (value) => formatDate(value, locale),
        dateTime: (value) => formatDateTime(value, locale),
    };
}

/**
 * Group an IBAN in blocks of four for reading, e.g. "OM60 0180 0000 …".
 */
export function formatIban(iban: string): string {
    return iban.replace(/(.{4})/g, '$1 ').trim();
}
