import { Link, usePage } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useLocale } from '@/lib/i18n';
import { navItemsFor, portalLabelFor } from '@/lib/navigation';
import { dashboard } from '@/routes';

export function AppSidebar() {
    const { auth } = usePage().props;
    const { isRtl } = useLocale();

    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            side={isRtl ? 'right' : 'left'}
        >
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain
                    label={portalLabelFor(auth.user.role)}
                    items={navItemsFor(auth.user.role)}
                />
            </SidebarContent>

            <SidebarFooter>
                <LocaleSwitcher className="justify-start group-data-[collapsible=icon]:hidden" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
