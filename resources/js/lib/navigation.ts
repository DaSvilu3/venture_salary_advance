import {
    Building2,
    Files,
    FileText,
    FlaskConical,
    FileSignature,
    HandCoins,
    Handshake,
    History,
    House,
    SlidersHorizontal,
    LayoutGrid,
    ReceiptText,
    Settings2,
    UserRound,
    Users,
    Workflow,
} from 'lucide-react';
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
                { title: tKey('Home'), href: app.home(), icon: House },
                {
                    title: tKey('Advances'),
                    href: app.advances.index(),
                    icon: ReceiptText,
                },
                {
                    title: tKey('Profile'),
                    href: app.profile(),
                    icon: UserRound,
                },
            ];
        case 'employer_admin':
            return [
                {
                    title: tKey('Dashboard'),
                    href: employer.dashboard(),
                    icon: LayoutGrid,
                },
                {
                    title: tKey('Employees'),
                    href: employer.employees.index(),
                    icon: Users,
                },
                {
                    title: tKey('Documents'),
                    href: employer.documents.index(),
                    icon: Files,
                },
                {
                    title: tKey('Agreement'),
                    href: employer.agreement.show(),
                    icon: FileSignature,
                },
                {
                    title: tKey('Company settings'),
                    href: employer.settings.edit(),
                    icon: Settings2,
                },
            ];
        case 'platform_admin':
            return [
                {
                    title: tKey('Dashboard'),
                    href: admin.dashboard(),
                    icon: LayoutGrid,
                },
                {
                    title: tKey('Advances'),
                    href: admin.advances.index(),
                    icon: HandCoins,
                },
                {
                    title: tKey('Employers'),
                    href: admin.employers.index(),
                    icon: Building2,
                },
                {
                    title: tKey('Partners'),
                    href: admin.partners.index(),
                    icon: Handshake,
                },
                {
                    title: tKey('Program terms'),
                    href: admin.terms.edit(),
                    icon: SlidersHorizontal,
                },
                {
                    title: tKey('Workflow'),
                    href: admin.workflow.show(),
                    icon: Workflow,
                },
                {
                    title: tKey('Disclosures'),
                    href: admin.disclosures.index(),
                    icon: FileText,
                },
                {
                    title: tKey('Audit log'),
                    href: admin.auditLog.index(),
                    icon: History,
                },
                {
                    title: tKey('Simulation'),
                    href: admin.simulation.show(),
                    icon: FlaskConical,
                },
            ];
        case 'partner_viewer':
            return [
                {
                    title: tKey('Dashboard'),
                    href: partner.dashboard(),
                    icon: LayoutGrid,
                },
                {
                    title: tKey('Advances'),
                    href: partner.advances.index(),
                    icon: HandCoins,
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
