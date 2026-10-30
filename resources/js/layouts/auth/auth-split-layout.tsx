import { Link, usePage } from '@inertiajs/react';
import {
    CalendarCheck,
    CheckCircle2,
    Languages,
    ShieldCheck,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { useDates } from '@/lib/format';
import { tKey, useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

const TRUST = [
    { icon: ShieldCheck, label: tKey('Licensed bank and funder partners') },
    { icon: CalendarCheck, label: tKey('Repaid by direct debit on payday') },
    { icon: Languages, label: tKey('Arabic and English') },
];

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;
    const t = useT();
    const money = useMoney();
    const { date } = useDates();

    return (
        <div className="relative grid min-h-svh lg:grid-cols-[1.05fr_1fr]">
            {/* Brand panel */}
            <aside className="relative hidden flex-col justify-between overflow-hidden bg-dawn p-10 lg:flex xl:p-14">
                <Link
                    href={home()}
                    className="relative z-10 inline-flex items-center gap-2.5"
                >
                    <span className="flex size-9 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-black/5 dark:bg-white/10">
                        <AppLogoIcon className="size-5 fill-current" />
                    </span>
                    <span className="text-lg font-semibold tracking-tight">
                        {name}
                    </span>
                </Link>

                <div className="relative z-10 max-w-md space-y-8">
                    <div className="space-y-4">
                        <span className="inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1 text-xs font-medium backdrop-blur dark:bg-black/40">
                            <span className="size-1.5 rounded-full bg-brand" />
                            {t('Prototype · not a live financial service')}
                        </span>
                        <h2 className="text-3xl leading-[1.1] font-semibold xl:text-4xl">
                            {t('Your salary, a little earlier.')}
                        </h2>
                        <p className="text-foreground/70">
                            {t(
                                'Small, short-term advances against verified salary, repaid automatically on payday.',
                            )}
                        </p>
                    </div>

                    {/* Product moment */}
                    <div
                        className="relative w-full max-w-xs rounded-3xl bg-white/90 p-5 shadow-xl shadow-black/5 backdrop-blur dark:bg-black/60"
                        aria-hidden="true"
                    >
                        <p className="text-sm text-muted-foreground">
                            {t('Available now')}
                        </p>
                        <p className="mt-1 text-3xl font-semibold tracking-tight tabular-nums">
                            {money(150_000)}
                        </p>
                        <div className="mt-4 h-1.5 overflow-hidden rounded-full bg-brand-soft">
                            <div className="h-full w-1/2 rounded-full bg-brand" />
                        </div>
                        <p className="mt-2 text-xs text-muted-foreground">
                            {t('Next payday :date', {
                                date: date('2026-10-25'),
                            })}
                        </p>
                        <div className="mt-5 flex items-center justify-between gap-3 rounded-2xl bg-brand-soft/70 px-4 py-2.5">
                            <div className="flex items-center gap-2 text-sm">
                                <span className="flex size-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                    <CheckCircle2 className="size-4" />
                                </span>
                                <span className="font-medium tabular-nums">
                                    +{money(75_000)}
                                </span>
                            </div>
                            <span className="text-xs text-muted-foreground">
                                {t('Paid into your account')}
                            </span>
                        </div>
                    </div>
                </div>

                <ul className="relative z-10 grid gap-2.5">
                    {TRUST.map((item) => (
                        <li
                            key={item.label}
                            className="flex items-center gap-2.5 text-sm text-foreground/80"
                        >
                            <item.icon className="size-4 shrink-0 text-brand" />
                            {t(item.label)}
                        </li>
                    ))}
                </ul>
            </aside>

            {/* Form panel */}
            <main className="relative flex min-h-svh items-center justify-center bg-dawn-soft px-4 py-10 sm:px-6 lg:bg-background">
                <LocaleSwitcher className="absolute end-4 top-4" />

                <div className="w-full max-w-md rounded-[2rem] bg-card p-6 shadow-xl ring-1 shadow-brand/5 ring-border/60 sm:p-10 lg:shadow-lg">
                    <div className="flex flex-col gap-8">
                        <div className="flex flex-col items-center gap-4 text-center">
                            <Link
                                href={home()}
                                className="flex size-12 items-center justify-center rounded-2xl bg-brand-soft lg:hidden"
                            >
                                <AppLogoIcon className="size-7 fill-current" />
                            </Link>
                            <div className="space-y-1.5">
                                <h1 className="text-2xl font-semibold tracking-tight">
                                    {title && t(title)}
                                </h1>
                                {description && (
                                    <p className="text-sm text-balance text-muted-foreground">
                                        {t(description)}
                                    </p>
                                )}
                            </div>
                        </div>
                        {children}
                    </div>
                </div>
            </main>
        </div>
    );
}
