import { useLocale } from '@/lib/i18n';
import type { Locale } from '@/lib/i18n';

export const BAISA_PER_OMR = 1000;

const amountFormatter = new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 3,
    maximumFractionDigits: 3,
});

/**
 * Format integer baisa as rials, e.g. 1250000 → "OMR 1,250.000" (or "1,250.000 ر.ع." in Arabic).
 * Western digits are used in both locales.
 */
export function formatOMR(baisa: number, locale: Locale = 'en'): string {
    const amount = amountFormatter.format(baisa / BAISA_PER_OMR);

    return locale === 'ar' ? `${amount} ر.ع.` : `OMR ${amount}`;
}

/**
 * Convert baisa to a rial number for inputs and sliders.
 */
export function baisaToOmr(baisa: number): number {
    return baisa / BAISA_PER_OMR;
}

export function omrToBaisa(omr: number): number {
    return Math.round(omr * BAISA_PER_OMR);
}

export function useMoney(): (baisa: number) => string {
    const { locale } = useLocale();

    return (baisa) => formatOMR(baisa, locale);
}
