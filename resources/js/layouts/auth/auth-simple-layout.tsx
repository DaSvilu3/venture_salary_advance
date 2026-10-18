import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { useT } from '@/lib/i18n';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const t = useT();

    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-dawn-soft p-4 md:p-10">
            <LocaleSwitcher className="absolute end-4 top-4" />
            <div className="w-full max-w-md rounded-[2rem] bg-card p-6 shadow-xl shadow-brand/5 sm:p-10">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <div className="mb-1 flex size-12 items-center justify-center rounded-2xl bg-brand-soft">
                                <AppLogoIcon className="size-7 fill-current" />
                            </div>
                            <span className="sr-only">{title && t(title)}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-2xl font-semibold">
                                {title && t(title)}
                            </h1>
                            <p className="text-center text-sm text-muted-foreground">
                                {description && t(description)}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
