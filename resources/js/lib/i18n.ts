import { usePage } from '@inertiajs/react';

export type Locale = 'en' | 'ar';

export type Replacements = Record<string, string | number>;

/**
 * Look up a translation keyed by its English source text, as in Laravel's JSON
 * translation files, and fill `:placeholder` replacements.
 */
export function translate(
    translations: Record<string, string>,
    key: string,
    replacements: Replacements = {},
): string {
    let line = translations[key] ?? key;

    for (const [name, value] of Object.entries(replacements)) {
        line = line.replaceAll(`:${name}`, String(value));
    }

    return line;
}

/**
 * Marks a string stored in data (nav items, breadcrumbs, card copy) as a
 * translation key, so the coverage test can find it. Translate it with `t()`
 * where it is rendered.
 */
export function tKey(key: string): string {
    return key;
}

export function useT(): (key: string, replacements?: Replacements) => string {
    const { translations } = usePage().props;

    return (key, replacements) => translate(translations, key, replacements);
}

export function useLocale(): {
    locale: Locale;
    direction: 'ltr' | 'rtl';
    isRtl: boolean;
} {
    const { locale, direction } = usePage().props;

    return { locale, direction, isRtl: direction === 'rtl' };
}
