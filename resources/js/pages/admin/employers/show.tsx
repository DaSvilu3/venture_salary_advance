import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download, UserPlus } from 'lucide-react';
import InputError from '@/components/input-error';
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
import type { AgreementStatus } from '@/lib/agreement';
import { agreementStatusMeta } from '@/lib/agreement';
import type { CompanyDocument } from '@/lib/documents';
import { documentStatusMeta, documentTypeMeta } from '@/lib/documents';
import { useDates } from '@/lib/format';
import { tKey, useT } from '@/lib/i18n';
import { employerStatusMeta } from '@/lib/roster-status';
import { index, update } from '@/routes/admin/employers';
import { show as showAgreement } from '@/routes/admin/employers/agreement';
import { download as downloadAgreement } from '@/routes/admin/employers/agreement/signed';
import {
    approve as approveDocument,
    download as downloadDocument,
    reject as rejectDocument,
} from '@/routes/admin/employers/documents';
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
    documents: CompanyDocument[];
    agreement: {
        status: AgreementStatus;
        decided_at: string | null;
        version: string;
        has_signed: boolean;
    };
    terms: TermsValues;
    hasOverride: boolean;
    partners: PartnerOptions;
};

export default function ShowEmployer({
    employer,
    admins,
    documents,
    agreement,
    terms,
    hasOverride,
    partners,
}: Props) {
    const t = useT();
    const { date } = useDates();

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
                    <h2 className="mb-1 font-medium">{t('Agreement')}</h2>
                    <p className="mb-4 text-sm text-muted-foreground">
                        {t(
                            'The programme agreement the employer accepts or rejects. Open it to review the terms and any signed copy.',
                        )}
                    </p>
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border px-3 py-3 text-sm">
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusPill
                                meta={agreementStatusMeta[agreement.status]}
                            />
                            <span className="text-muted-foreground">
                                {t('Version :v', { v: agreement.version })}
                                {agreement.decided_at &&
                                    ` · ${date(agreement.decided_at)}`}
                            </span>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <Button asChild variant="ghost" size="sm">
                                <Link href={showAgreement(employer.id)}>
                                    {t('View agreement')}
                                </Link>
                            </Button>
                            {agreement.has_signed && (
                                <Button asChild variant="ghost" size="sm">
                                    <a
                                        href={downloadAgreement.url(
                                            employer.id,
                                        )}
                                    >
                                        <Download />
                                        {t('Signed copy')}
                                    </a>
                                </Button>
                            )}
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border bg-card p-4">
                    <h2 className="mb-1 font-medium">
                        {t('Company documents')}
                    </h2>
                    <p className="mb-4 text-sm text-muted-foreground">
                        {t(
                            'Review each document. Approve it, or reject it with a reason the employer will see.',
                        )}
                    </p>
                    <ul className="divide-y rounded-lg border">
                        {documents.length === 0 && (
                            <li className="px-3 py-2 text-sm text-muted-foreground">
                                {t('Nothing uploaded yet.')}
                            </li>
                        )}
                        {documents.map((item) => (
                            <li
                                key={item.id}
                                className="grid gap-3 px-3 py-3 text-sm lg:grid-cols-[1fr_auto] lg:items-center"
                            >
                                <div className="space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-medium">
                                            {t(
                                                documentTypeMeta[item.type]
                                                    ?.label ?? item.type,
                                            )}
                                        </span>
                                        <StatusPill
                                            meta={
                                                documentStatusMeta[item.status]
                                            }
                                        />
                                    </div>
                                    <p
                                        className="break-all text-muted-foreground"
                                        dir="ltr"
                                    >
                                        {item.original_name}
                                        {item.uploaded_at &&
                                            ` · ${date(item.uploaded_at)}`}
                                    </p>
                                    {item.review_note && (
                                        <p className="text-xs text-muted-foreground">
                                            {t('Note:')} {item.review_note}
                                        </p>
                                    )}
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <Button asChild variant="ghost" size="sm">
                                        <a
                                            href={downloadDocument.url([
                                                employer.id,
                                                item.id,
                                            ])}
                                        >
                                            <Download />
                                            {t('Download')}
                                        </a>
                                    </Button>
                                    {item.status !== 'approved' && (
                                        <Form
                                            {...approveDocument.form([
                                                employer.id,
                                                item.id,
                                            ])}
                                            options={{ preserveScroll: true }}
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    size="sm"
                                                    disabled={processing}
                                                >
                                                    {t('Approve')}
                                                </Button>
                                            )}
                                        </Form>
                                    )}
                                    {item.status !== 'rejected' && (
                                        <Form
                                            {...rejectDocument.form([
                                                employer.id,
                                                item.id,
                                            ])}
                                            resetOnSuccess
                                            className="flex items-center gap-2"
                                            options={{ preserveScroll: true }}
                                        >
                                            {({ processing, errors }) => (
                                                <>
                                                    <Input
                                                        name="note"
                                                        required
                                                        maxLength={255}
                                                        placeholder={t(
                                                            'Reason for rejection',
                                                        )}
                                                        aria-label={t(
                                                            'Reason for rejection',
                                                        )}
                                                        className="h-8 w-48"
                                                    />
                                                    <Button
                                                        type="submit"
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={processing}
                                                    >
                                                        {t('Reject')}
                                                    </Button>
                                                    <InputError
                                                        message={errors.note}
                                                    />
                                                </>
                                            )}
                                        </Form>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>

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
