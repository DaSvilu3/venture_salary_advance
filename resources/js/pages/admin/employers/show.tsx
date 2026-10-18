import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, UserPlus } from 'lucide-react';
import { Field } from '@/components/portal/field';
import { PageHeader } from '@/components/portal/page-header';
import { PartnerSelect } from '@/components/portal/partner-select';
import { StatusPill } from '@/components/portal/status-pill';
import { TermsForm } from '@/components/portal/terms-form';
import type { TermsValues } from '@/components/portal/terms-form';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { tKey, useT } from '@/lib/i18n';
import { employerStatusMeta } from '@/lib/roster-status';
import { index, update } from '@/routes/admin/employers';
import { store as storeAdmin } from '@/routes/admin/employers/admins';
import {
    destroy as destroyTerms,
    update as updateTerms,
} from '@/routes/admin/employers/terms';
import type { PartnerOptions } from './index';

type Props = {
    employer: {
        id: number;
        name: string;
        cr_number: string;
        status: string;
        payday_day: number;
        bank_partner_id: number | null;
        funder_partner_id: number | null;
        employees_count: number;
    };
    admins: { id: number; name: string; email: string }[];
    terms: TermsValues;
    hasOverride: boolean;
    partners: PartnerOptions;
};

export default function ShowEmployer({
    employer,
    admins,
    terms,
    hasOverride,
    partners,
}: Props) {
    const t = useT();

    return (
        <>
            <Head title={employer.name} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Link
                    href={index()}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground"
                >
                    <ArrowLeft className="size-4 rtl:rotate-180" />
                    {t('Employers')}
                </Link>

                <PageHeader
                    title={employer.name}
                    description={t(
                        'CR :number · :count employees on the roster',
                        {
                            number: employer.cr_number,
                            count: employer.employees_count,
                        },
                    )}
                    actions={
                        <StatusPill
                            meta={employerStatusMeta[employer.status]}
                        />
                    }
                />

                <div className="grid gap-6 xl:grid-cols-2">
                    <section className="rounded-xl border bg-card p-4">
                        <h2 className="mb-4 font-medium">{t('Company')}</h2>
                        <Form
                            {...update.form(employer.id)}
                            className="grid gap-4 sm:grid-cols-2"
                            options={{ preserveScroll: true }}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <Field
                                        label={t('Name')}
                                        htmlFor="name"
                                        error={errors.name}
                                        className="sm:col-span-2"
                                    >
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={employer.name}
                                            required
                                        />
                                    </Field>
                                    <Field
                                        label={t('Status')}
                                        error={errors.status}
                                    >
                                        <Select
                                            name="status"
                                            defaultValue={employer.status}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {Object.entries(
                                                    employerStatusMeta,
                                                ).map(([value, meta]) => (
                                                    <SelectItem
                                                        key={value}
                                                        value={value}
                                                    >
                                                        {t(meta.label)}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <Field
                                        label={t('Payday (day of the month)')}
                                        htmlFor="payday_day"
                                        error={errors.payday_day}
                                    >
                                        <Input
                                            id="payday_day"
                                            name="payday_day"
                                            type="number"
                                            min={1}
                                            max={31}
                                            defaultValue={employer.payday_day}
                                            required
                                            dir="ltr"
                                        />
                                    </Field>
                                    <Field
                                        label={t('Bank partner')}
                                        error={errors.bank_partner_id}
                                    >
                                        <PartnerSelect
                                            name="bank_partner_id"
                                            options={partners.banks}
                                            defaultValue={
                                                employer.bank_partner_id
                                            }
                                        />
                                    </Field>
                                    <Field
                                        label={t('Funding partner')}
                                        error={errors.funder_partner_id}
                                    >
                                        <PartnerSelect
                                            name="funder_partner_id"
                                            options={partners.funders}
                                            defaultValue={
                                                employer.funder_partner_id
                                            }
                                        />
                                    </Field>
                                    <div className="sm:col-span-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            {t('Save')}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </section>

                    <section className="rounded-xl border bg-card p-4">
                        <h2 className="mb-1 font-medium">
                            {t('HR administrators')}
                        </h2>
                        <p className="mb-4 text-sm text-muted-foreground">
                            {t(
                                'They manage the roster and approve enrolments for this company.',
                            )}
                        </p>
                        <ul className="mb-4 divide-y rounded-lg border">
                            {admins.length === 0 && (
                                <li className="px-3 py-2 text-sm text-muted-foreground">
                                    {t('None yet.')}
                                </li>
                            )}
                            {admins.map((admin) => (
                                <li
                                    key={admin.id}
                                    className="px-3 py-2 text-sm"
                                >
                                    <span className="font-medium">
                                        {admin.name}
                                    </span>{' '}
                                    <span
                                        className="text-muted-foreground"
                                        dir="ltr"
                                    >
                                        {admin.email}
                                    </span>
                                </li>
                            ))}
                        </ul>
                        <Form
                            {...storeAdmin.form(employer.id)}
                            resetOnSuccess
                            className="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                            options={{ preserveScroll: true }}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <Field
                                        label={t('Name')}
                                        htmlFor="admin_name"
                                        error={errors.name}
                                    >
                                        <Input
                                            id="admin_name"
                                            name="name"
                                            required
                                        />
                                    </Field>
                                    <Field
                                        label={t('Email address')}
                                        htmlFor="admin_email"
                                        error={errors.email}
                                    >
                                        <Input
                                            id="admin_email"
                                            name="email"
                                            type="email"
                                            required
                                            dir="ltr"
                                        />
                                    </Field>
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        {processing ? (
                                            <Spinner />
                                        ) : (
                                            <UserPlus />
                                        )}
                                        {t('Invite')}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>
                </div>

                <section className="rounded-xl border bg-card p-4">
                    <div className="mb-4 flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h2 className="font-medium">
                                {t('Program terms')}
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {hasOverride
                                    ? t('This employer has its own terms.')
                                    : t(
                                          'Using the platform terms. Saving here creates terms for this employer only.',
                                      )}
                            </p>
                        </div>
                        {hasOverride && (
                            <Form
                                {...destroyTerms.form(employer.id)}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="ghost"
                                        size="sm"
                                        disabled={processing}
                                    >
                                        {t('Use platform terms')}
                                    </Button>
                                )}
                            </Form>
                        )}
                    </div>
                    <TermsForm
                        action={updateTerms.form(employer.id)}
                        values={terms}
                        submitLabel={t('Save terms for this employer')}
                    />
                </section>
            </div>
        </>
    );
}

ShowEmployer.layout = {
    breadcrumbs: [{ title: tKey('Employers'), href: index() }],
};
