import { Head, Link, usePage } from '@inertiajs/react';
import { BadgeCheck, Building2, Landmark } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { Button } from '@/components/ui/button';
import { tKey, useT } from '@/lib/i18n';
import { dashboard, login } from '@/routes';

export default function Welcome() {
    const { auth, name } = usePage().props;
    const t = useT();

    const points = [
        {
            icon: BadgeCheck,
            title: tKey('Against verified salary'),
            body: tKey(
                'Draw a small amount before payday, within a limit set from your verified salary.',
            ),
        },
        {
            icon: Building2,
            title: tKey('Offered by your employer'),
            body: tKey(
                'A financial-wellness benefit your employer turns on for staff.',
            ),
        },
        {
            icon: Landmark,
            title: tKey('Handled by a licensed bank'),
            body: tKey(
                'Money goes to your own account and is collected on payday by direct debit.',
            ),
        },
    ];

    return (
        <>
            <Head title={t('Welcome')} />
            <div className="flex min-h-svh flex-col bg-background">
                <header className="mx-auto flex w-full max-w-5xl items-center gap-3 px-4 py-4">
                    <AppLogoIcon className="size-8 fill-current text-primary" />
                    <span className="font-semibold">{name}</span>
                    <div className="ms-auto flex items-center gap-2">
                        <LocaleSwitcher />
                        <Button asChild size="sm">
                            <Link href={auth.user ? dashboard() : login()}>
                                {t(auth.user ? 'Open portal' : 'Log in')}
                            </Link>
                        </Button>
                    </div>
                </header>

                <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center gap-12 px-4 py-12">
                    <div className="max-w-2xl space-y-4">
                        <p className="text-sm font-medium text-muted-foreground">
                            {t('Prototype · not a live financial service')}
                        </p>
                        <h1 className="text-3xl font-semibold tracking-tight md:text-5xl">
                            {t('Your salary, a little earlier.')}
                        </h1>
                        <p className="text-lg text-muted-foreground">
                            {t(
                                'Small, short-term advances against verified salary, repaid automatically on payday.',
                            )}
                        </p>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        {points.map((point) => (
                            <div
                                key={point.title}
                                className="rounded-xl border p-5"
                            >
                                <point.icon className="mb-3 size-6 text-primary" />
                                <h2 className="font-medium">
                                    {t(point.title)}
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {t(point.body)}
                                </p>
                            </div>
                        ))}
                    </div>
                </main>
            </div>
        </>
    );
}
