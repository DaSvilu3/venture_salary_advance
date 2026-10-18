import { useState } from 'react';
import { useLocale, useT } from '@/lib/i18n';
import { BAISA_PER_OMR, useMoney } from '@/lib/money';

export type MonthlySeries = {
    key: 'disbursed' | 'collected';
    label: string;
    color: string;
};

type Row = { month: string } & Record<MonthlySeries['key'], number>;

const PLOT_HEIGHT = 200;

/**
 * Grouped columns for a few monthly money series (baisa). One axis, a legend,
 * a hover tooltip per month and a table view.
 */
export function MonthlyColumns({
    rows,
    series,
    title,
}: {
    rows: Row[];
    series: MonthlySeries[];
    title: string;
}) {
    const t = useT();
    const money = useMoney();
    const { locale } = useLocale();
    const [hovered, setHovered] = useState<string | null>(null);

    const max = Math.max(
        1,
        ...rows.flatMap((row) => series.map((s) => row[s.key])),
    );
    const ticks = niceTicks(max / BAISA_PER_OMR);
    const top = ticks[ticks.length - 1] * BAISA_PER_OMR;
    const compact = new Intl.NumberFormat('en-US', {
        notation: 'compact',
        maximumFractionDigits: 1,
    });
    const monthLabel = (month: string) =>
        new Intl.DateTimeFormat(locale === 'ar' ? 'ar-OM-u-nu-latn' : 'en-GB', {
            month: 'short',
        }).format(new Date(`${month}-01T00:00:00`));

    return (
        <figure className="space-y-3">
            <figcaption className="flex flex-wrap items-center justify-between gap-2">
                <span className="font-medium">{title}</span>
                <span className="flex items-center gap-4 text-xs text-muted-foreground">
                    {series.map((s) => (
                        <span
                            key={s.key}
                            className="inline-flex items-center gap-1.5"
                        >
                            <span
                                className="size-2.5 rounded-sm"
                                style={{ backgroundColor: s.color }}
                            />
                            {s.label}
                        </span>
                    ))}
                </span>
            </figcaption>

            <div className="flex gap-2" dir="ltr">
                <div
                    className="relative w-10 shrink-0 text-end text-[11px] text-muted-foreground"
                    style={{ height: PLOT_HEIGHT }}
                >
                    {ticks.map((tick) => (
                        <span
                            key={tick}
                            className="absolute end-0 tabular-nums"
                            style={{
                                bottom: `${((tick * BAISA_PER_OMR) / top) * 100}%`,
                                transform: 'translateY(50%)',
                            }}
                        >
                            {compact.format(tick)}
                        </span>
                    ))}
                </div>

                <div className="relative flex-1">
                    <div className="relative" style={{ height: PLOT_HEIGHT }}>
                        {ticks.map((tick) => (
                            <div
                                key={tick}
                                className="absolute inset-x-0 border-t border-border/70"
                                style={{
                                    bottom: `${((tick * BAISA_PER_OMR) / top) * 100}%`,
                                }}
                            />
                        ))}
                        <div className="absolute inset-0 flex items-end justify-around">
                            {rows.map((row) => (
                                <div
                                    key={row.month}
                                    className="relative flex h-full flex-1 items-end justify-center gap-0.5 rounded-md hover:bg-muted/50"
                                    onMouseEnter={() => setHovered(row.month)}
                                    onMouseLeave={() => setHovered(null)}
                                >
                                    {series.map((s) => (
                                        <div
                                            key={s.key}
                                            className="w-full max-w-6 rounded-t"
                                            style={{
                                                height: `${(row[s.key] / top) * 100}%`,
                                                minHeight:
                                                    row[s.key] > 0 ? 2 : 0,
                                                backgroundColor: s.color,
                                            }}
                                        />
                                    ))}
                                    {hovered === row.month && (
                                        <div
                                            className="pointer-events-none absolute top-2 left-1/2 z-10 -translate-x-1/2 rounded-lg border bg-popover p-2 text-xs whitespace-nowrap shadow-md"
                                            dir={
                                                locale === 'ar' ? 'rtl' : 'ltr'
                                            }
                                        >
                                            <p className="mb-1 font-medium">
                                                {monthLabel(row.month)}
                                            </p>
                                            {series.map((s) => (
                                                <p
                                                    key={s.key}
                                                    className="flex items-center justify-between gap-3"
                                                >
                                                    <span className="inline-flex items-center gap-1.5 text-muted-foreground">
                                                        <span
                                                            className="size-2 rounded-sm"
                                                            style={{
                                                                backgroundColor:
                                                                    s.color,
                                                            }}
                                                        />
                                                        {s.label}
                                                    </span>
                                                    <span className="tabular-nums">
                                                        {money(row[s.key])}
                                                    </span>
                                                </p>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="mt-1 flex justify-around text-[11px] text-muted-foreground">
                        {rows.map((row) => (
                            <span
                                key={row.month}
                                className="flex-1 text-center"
                            >
                                {monthLabel(row.month)}
                            </span>
                        ))}
                    </div>
                </div>
            </div>

            <details className="text-sm">
                <summary className="cursor-pointer text-xs text-muted-foreground">
                    {t('Show as a table')}
                </summary>
                <table className="mt-2 w-full text-xs">
                    <thead>
                        <tr className="text-muted-foreground">
                            <th className="py-1 text-start font-medium">
                                {t('Month')}
                            </th>
                            {series.map((s) => (
                                <th
                                    key={s.key}
                                    className="py-1 text-end font-medium"
                                >
                                    {s.label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.month} className="border-t">
                                <td className="py-1">
                                    {monthLabel(row.month)}
                                </td>
                                {series.map((s) => (
                                    <td
                                        key={s.key}
                                        className="py-1 text-end tabular-nums"
                                    >
                                        {money(row[s.key])}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </details>
        </figure>
    );
}

/**
 * Clean axis ticks (0 and multiples of 1, 2 or 5 × 10ⁿ) covering the maximum in about four steps.
 */
function niceTicks(max: number): number[] {
    const rough = max / 4;
    const magnitude = 10 ** Math.floor(Math.log10(rough || 1));
    const step =
        [1, 2, 5, 10]
            .map((m) => m * magnitude)
            .find((candidate) => candidate >= rough) ?? magnitude * 10;
    const ticks: number[] = [];

    for (
        let value = 0;
        value <= max + step * 0.0001 || ticks.length < 2;
        value += step
    ) {
        ticks.push(Math.round(value * 1000) / 1000);
    }

    if (ticks[ticks.length - 1] < max) {
        ticks.push(ticks[ticks.length - 1] + step);
    }

    return ticks;
}
