import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { SimulatedDateBanner } from '@/components/simulated-date-banner';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useT } from '@/lib/i18n';
import { navItemsFor } from '@/lib/navigation';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

/**
 * Mobile-first shell for the employee portal: a slim top bar, a single
 * centred column and a bottom tab bar.
 */
export default function EmployeeLayout({ children }: { children: ReactNode }) {
    const { auth, name } = usePage().props;
    const { isCurrentUrl } = useCurrentUrl();
    const t = useT();
    const items = navItemsFor('employee');

    return (
        <div className="flex min-h-svh flex-col bg-muted/40">
            <SimulatedDateBanner />

            <header className="sticky top-0 z-20 border-b bg-background/95 backdrop-blur">
                <div className="mx-auto flex h-14 w-full max-w-md items-center gap-2 px-4">
                    <Link
                        href={dashboard()}
                        className="flex items-center gap-2 font-semibold"
                    >
                        <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-5 fill-current" />
                        </span>
                        <span className="truncate text-sm">{name}</span>
                    </Link>

                    <div className="ms-auto flex items-center gap-1">
                        <LocaleSwitcher />
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                className="rounded-full"
                                data-test="employee-user-menu"
                            >
                                <span className="sr-only">
                                    {t('Account menu')}
                                </span>
                                <span className="flex items-center">
                                    <UserInfo user={auth.user} avatarOnly />
                                </span>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <UserMenuContent user={auth.user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </header>

            <main className="mx-auto w-full max-w-md flex-1 px-4 pt-4 pb-24">
                {children}
            </main>

            {items.length > 1 && (
                <nav className="fixed inset-x-0 bottom-0 z-20 border-t bg-background pb-[env(safe-area-inset-bottom)]">
                    <div className="mx-auto flex max-w-md">
                        {items.map((item) => (
                            <Link
                                key={item.title}
                                href={item.href}
                                prefetch
                                className={cn(
                                    'flex flex-1 flex-col items-center gap-1 py-2 text-xs text-muted-foreground',
                                    isCurrentUrl(item.href) &&
                                        'font-medium text-primary',
                                )}
                            >
                                {item.icon && <item.icon className="size-5" />}
                                {t(item.title)}
                            </Link>
                        ))}
                    </div>
                </nav>
            )}
        </div>
    );
}
