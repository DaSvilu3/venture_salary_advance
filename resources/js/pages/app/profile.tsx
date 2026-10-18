import { Head, Link } from '@inertiajs/react';
import { BadgeCheck, ChevronRight, KeyRound } from 'lucide-react';
import { formatIban, useDates } from '@/lib/format';
import { useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { onboarding } from '@/routes/app';
import { edit as editSecurity } from '@/routes/security';

type Props = {
    profile: {
        name: string;
        email: string;
        staff_no: string;
        civil_id: string;
        employer: string;
        net_salary: number;
        salary_verified: boolean;
        iban: string | null;
        bank: string | null;
        mandate: { reference: string; signed_at: string | null } | null;
    };
};

export default function Profile({ profile }: Props) {
    const t = useT();
    const money = useMoney();
    const { date } = useDates();

    return (
        <>
            <Head title={t('Profile')} />

            <h1 className="text-xl font-semibold">{profile.name}</h1>
            <p className="text-sm text-muted-foreground" dir="ltr">
                {profile.email}
            </p>

            <Section title={t('Employment')}>
                <Row label={t('Employer')} value={profile.employer} />
                <Row label={t('Staff number')} value={profile.staff_no} ltr />
                <Row label={t('Civil ID')} value={profile.civil_id} ltr />
                <Row
                    label={t('Net monthly salary')}
                    value={
                        <span className="inline-flex items-center gap-1">
                            {money(profile.net_salary)}
                            {profile.salary_verified && (
                                <BadgeCheck
                                    className="size-4 text-emerald-600"
                                    aria-label={t('Verified')}
                                />
                            )}
                        </span>
                    }
                />
            </Section>

            <Section title={t('Bank and mandate')}>
                <Row label={t('Bank')} value={profile.bank ?? '—'} />
                <Row
                    label="IBAN"
                    value={profile.iban ? formatIban(profile.iban) : '—'}
                    ltr
                />
                <Row
                    label={t('Mandate')}
                    value={
                        profile.mandate
                            ? `${profile.mandate.reference}${profile.mandate.signed_at ? ` · ${date(profile.mandate.signed_at)}` : ''}`
                            : t('Not signed')
                    }
                />
                <Link
                    href={onboarding()}
                    className="flex items-center justify-between py-3 text-sm text-primary"
                >
                    {t('Change bank account')}
                    <ChevronRight className="size-4 rtl:rotate-180" />
                </Link>
            </Section>

            <Section title={t('Security')}>
                <Link
                    href={editSecurity()}
                    className="flex items-center justify-between py-3 text-sm"
                >
                    <span className="inline-flex items-center gap-2">
                        <KeyRound className="size-4" />
                        {t('Password and sign-in')}
                    </span>
                    <ChevronRight className="size-4 rtl:rotate-180" />
                </Link>
            </Section>
        </>
    );
}

function Section({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="mt-6">
            <h2 className="mb-2 text-sm font-medium text-muted-foreground">
                {title}
            </h2>
            <div className="divide-y rounded-xl border bg-card px-4">
                {children}
            </div>
        </section>
    );
}

function Row({
    label,
    value,
    ltr = false,
}: {
    label: string;
    value: React.ReactNode;
    ltr?: boolean;
}) {
    return (
        <div className="flex items-center justify-between gap-4 py-3 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span
                className="text-end font-medium"
                dir={ltr ? 'ltr' : undefined}
            >
                {value}
            </span>
        </div>
    );
}
