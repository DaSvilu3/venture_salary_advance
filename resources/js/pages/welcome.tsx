import { Head, Link, usePage } from '@inertiajs/react';
import {
    BadgeCheck,
    Building2,
    CalendarCheck,
    CheckCircle2,
    Landmark,
    Languages,
    ShieldCheck,
    Wallet,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { Button } from '@/components/ui/button';
import { useDates } from '@/lib/format';
import { tKey, useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { dashboard, login } from '@/routes';

const FEATURES = [
    {
        icon: BadgeCheck,
        title: tKey('Against verified salary'),
        body: tKey(
            'Draw a small amount before payday, within a limit set from your verified salary.',
        ),
        points: [
            tKey('Up to a share of your net salary'),
            tKey('One fixed fee, shown upfront'),
        ],
    },
    {
        icon: Building2,
        title: tKey('Offered by your employer'),
        body: tKey(
            'A financial-wellness benefit your employer turns on for staff.',
        ),
        points: [
            tKey('No payroll deductions'),
            tKey('HR never sees your advances'),
        ],
    },
    {
        icon: Landmark,
        title: tKey('Handled by a licensed bank'),
        body: tKey(
            'Money goes to your own account and is collected on payday by direct debit.',
        ),
        points: [
            tKey('Direct-debit mandate you sign once'),
            tKey('Every step recorded'),
        ],
    },
];

const STEPS = [
    {
        title: tKey('Your employer invites you'),
        body: tKey(
            'HR adds you to the programme and you get a personal invitation link.',
        ),
    },
    {
        title: tKey('Connect your bank account'),
        body: tKey(
            'Add your IBAN and sign a direct-debit mandate with the bank, once.',
        ),
    },
    {
        title: tKey('Draw, then repay on payday'),
        body: tKey(
            'Choose an amount, see the fee, confirm. It is collected automatically on payday.',
        ),
    },
];

const TRUST = [
    { icon: ShieldCheck, label: tKey('Licensed bank and funder partners') },
    { icon: CalendarCheck, label: tKey('Repaid by direct debit on payday') },
    { icon: Wallet, label: tKey('Salary verified with payroll') },
    { icon: Languages, label: tKey('Arabic and English') },
];

export default function Welcome() {
    const { auth, name } = usePage().props;
    const t = useT();
    const money = useMoney();
    const { date } = useDates();
    const portalHref = auth.user ? dashboard() : login();

    return (
        <>
            <Head title={t('Welcome')} />
            <div className="min-h-svh bg-dawn-soft px-3 py-3 sm:px-6 sm:py-6">
                {/* Hero */}
                <section className="relative mx-auto max-w-6xl overflow-hidden rounded-[2rem] bg-dawn px-4 pt-4 pb-16 sm:px-8 sm:pb-20">
                    <header className="flex items-center gap-3 rounded-full bg-white/90 py-2 ps-4 pe-2 shadow-sm backdrop-blur dark:bg-black/60">
                        <span className="flex size-8 items-center justify-center rounded-xl bg-brand text-brand-foreground">
                            <AppLogoIcon className="size-5 fill-current" />
                        </span>
                        <span className="hidden font-semibold tracking-tight sm:inline">
                            {name}
                        </span>
                        <nav className="ms-6 hidden items-center gap-6 text-sm text-muted-foreground md:flex">
                            <a
                                href="#features"
                                className="hover:text-foreground"
                            >
                                {t('Why it works')}
                            </a>
                            <a href="#how" className="hover:text-foreground">
                                {t('How it works')}
                            </a>
                        </nav>
                        <div className="ms-auto flex items-center gap-1">
                            <LocaleSwitcher />
                            <Button asChild size="sm">
                                <Link href={portalHref}>
                                    {t(auth.user ? 'Open portal' : 'Log in')}
                                </Link>
                            </Button>
                        </div>
                    </header>

                    <div className="mt-12 grid items-center gap-14 lg:mt-20 lg:grid-cols-[1.1fr_1fr]">
                        <div className="space-y-6">
                            <span className="inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1 text-xs font-medium backdrop-blur dark:bg-black/40">
                                <span className="size-1.5 rounded-full bg-brand" />
                                {t('Prototype · not a live financial service')}
                            </span>
                            <h1 className="text-4xl leading-[1.05] font-semibold sm:text-6xl">
                                {t('Your salary, a little earlier.')}
                            </h1>
                            <p className="max-w-md text-lg text-foreground/75">
                                {t(
                                    'Small, short-term advances against verified salary, repaid automatically on payday.',
                                )}
                            </p>
                            <div className="flex flex-wrap gap-3">
                                <Button asChild size="lg">
                                    <Link href={portalHref}>
                                        {t(
                                            auth.user
                                                ? 'Open portal'
                                                : 'Log in',
                                        )}
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    size="lg"
                                    variant="outline"
                                    className="border-transparent bg-white/80 backdrop-blur hover:bg-white"
                                >
                                    <a href="#how">{t('How it works')}</a>
                                </Button>
                            </div>
                        </div>

                        {/* Product moments instead of stock photography */}
                        <div
                            className="relative mx-auto w-full max-w-sm"
                            aria-hidden="true"
                        >
                            <div className="rounded-3xl bg-white/90 p-5 shadow-xl backdrop-blur dark:bg-black/70">
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
                                <div className="mt-5 rounded-full bg-primary py-2.5 text-center text-sm font-medium text-primary-foreground">
                                    {t('Get an advance')}
                                </div>
                            </div>
                            <div className="absolute -start-4 -bottom-8 flex items-center gap-3 rounded-2xl bg-white/95 px-4 py-3 shadow-lg dark:bg-black/80">
                                <span className="flex size-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                    <CheckCircle2 className="size-4" />
                                </span>
                                <div>
                                    <p className="text-sm font-semibold tabular-nums">
                                        +{money(75_000)}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {t('Paid into your account')}
                                    </p>
                                </div>
                            </div>
                            <div className="absolute -end-2 -top-6 rounded-2xl bg-white/95 px-4 py-2.5 shadow-lg dark:bg-black/80">
                                <p className="text-xs text-muted-foreground">
                                    {t('Service fee')}
                                </p>
                                <p className="text-sm font-semibold tabular-nums">
                                    {money(2_500)}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Features */}
                <section
                    id="features"
                    className="mx-auto mt-4 max-w-6xl rounded-[2rem] bg-card px-4 py-14 sm:mt-6 sm:px-10"
                >
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-semibold sm:text-4xl">
                            {t('Money on your terms')}
                        </h2>
                        <p className="mt-3 text-muted-foreground">
                            {t(
                                'For the moments that cannot wait for payday, with a clear fee and no surprises.',
                            )}
                        </p>
                    </div>
                    <div className="mt-10 grid gap-4 md:grid-cols-3">
                        {FEATURES.map((feature) => (
                            <div
                                key={feature.title}
                                className="rounded-3xl bg-brand-soft/70 p-6"
                            >
                                <span className="flex size-11 items-center justify-center rounded-full bg-white text-brand shadow-sm dark:bg-black/40">
                                    <feature.icon className="size-5" />
                                </span>
                                <h3 className="mt-5 text-lg font-semibold">
                                    {t(feature.title)}
                                </h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {t(feature.body)}
                                </p>
                                <ul className="mt-4 space-y-2 text-sm">
                                    {feature.points.map((point) => (
                                        <li
                                            key={point}
                                            className="flex items-center gap-2"
                                        >
                                            <CheckCircle2 className="size-4 shrink-0 text-brand" />
                                            {t(point)}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                </section>

                {/* How it works */}
                <section
                    id="how"
                    className="mx-auto mt-4 max-w-6xl rounded-[2rem] bg-card px-4 py-14 sm:mt-6 sm:px-10"
                >
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-semibold sm:text-4xl">
                            {t('Quick, and safe by design.')}
                        </h2>
                        <p className="mt-3 text-muted-foreground">
                            {t(
                                'Three steps, once. After that an advance takes less than a minute.',
                            )}
                        </p>
                    </div>
                    <ol className="mt-10 grid gap-4 md:grid-cols-3">
                        {STEPS.map((step, index) => (
                            <li
                                key={step.title}
                                className="relative overflow-hidden rounded-3xl border bg-background p-6"
                            >
                                <span className="text-sm font-semibold text-brand tabular-nums">
                                    0{index + 1}
                                </span>
                                <h3 className="mt-3 text-lg font-semibold">
                                    {t(step.title)}
                                </h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {t(step.body)}
                                </p>
                                <div className="pointer-events-none absolute -end-12 -top-12 size-32 rounded-full bg-dawn opacity-30 blur-2xl" />
                            </li>
                        ))}
                    </ol>

                    <ul className="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {TRUST.map((item) => (
                            <li
                                key={item.label}
                                className="flex items-center gap-3 rounded-2xl bg-muted/60 px-4 py-3 text-sm"
                            >
                                <item.icon className="size-4 shrink-0 text-brand" />
                                {t(item.label)}
                            </li>
                        ))}
                    </ul>
                </section>

                <footer className="mx-auto mt-4 max-w-6xl rounded-[2rem] bg-card px-6 py-8 text-xs text-muted-foreground sm:mt-6 sm:px-10">
                    <div className="flex items-center gap-2 text-sm font-semibold text-foreground">
                        <span className="flex size-6 items-center justify-center rounded-lg bg-brand text-brand-foreground">
                            <AppLogoIcon className="size-4 fill-current" />
                        </span>
                        {name}
                    </div>
                    <p className="mt-3 max-w-3xl">
                        {t(
                            'Prototype for evaluation. AWJ provides the technology only; advances would be paid, collected and funded by licensed partners under Central Bank of Oman rules. No real money moves in this demo.',
                        )}
                    </p>
                </footer>
            </div>
        </>
    );
}
