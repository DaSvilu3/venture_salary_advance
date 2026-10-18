import { LayoutGrid } from 'lucide-react';
import admin from '@/routes/admin';
import app from '@/routes/app';
import employer from '@/routes/employer';
import partner from '@/routes/partner';
import { tKey } from '@/lib/i18n';
import type { NavItem, Role } from '@/types';

/**
 * The sidebar or bottom-bar items for each portal. Titles are English
 * translation keys.
 */
export function navItemsFor(role: Role): NavItem[] {
    switch (role) {
        case 'employee':
            return [
                { title: tKey('Home'), href: app.home(), icon: LayoutGrid },
            ];
        case 'employer_admin':
            return [
                {
                    title: tKey('Dashboard'),
                    href: employer.dashboard(),
                    icon: LayoutGrid,
                },
            ];
        case 'platform_admin':
            return [
                {
                    title: tKey('Dashboard'),
                    href: admin.dashboard(),
                    icon: LayoutGrid,
                },
            ];
        case 'partner_viewer':
            return [
                {
                    title: tKey('Dashboard'),
                    href: partner.dashboard(),
                    icon: LayoutGrid,
                },
            ];
    }
}

export function portalLabelFor(role: Role): string {
    switch (role) {
        case 'employee':
            return tKey('Employee');
        case 'employer_admin':
            return tKey('Employer portal');
        case 'platform_admin':
            return tKey('Platform admin');
        case 'partner_viewer':
            return tKey('Partner portal');
    }
}
