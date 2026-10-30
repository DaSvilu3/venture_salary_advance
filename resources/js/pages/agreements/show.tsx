import { Form, Head, useForm } from '@inertiajs/react';
import { Check, Download, FileUp, Printer, ShieldCheck, X } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useRef, useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import InputError from '@/components/input-error';
import { Field } from '@/components/portal/field';
import { StatusPill } from '@/components/portal/status-pill';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { AgreementStatus } from '@/lib/agreement';
import { agreementStatusMeta } from '@/lib/agreement';
import { useDates } from '@/lib/format';
import { tKey, useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import agreement from '@/routes/employer/agreement';
import { download as downloadAdminSigned } from '@/routes/admin/employers/agreement/signed';

type Props = {
    context: 'employer' | 'admin';
    employer: { id: number; name: string; cr_number: string };
    bank: string | null;
    funder: string | null;
    terms: {
        flat_fee: number;
        limit_ratio_bps: number;
        max_limit: number;
        min_advance: number;
        collection_max_attempts: number;
        collection_retry_days: number;
    };
    agreement: {
        version: string;
        status: AgreementStatus;
        decided_at: string | null;
        reject_reason: string | null;
        signed: { name: string; uploaded_at: string | null } | null;
    };
    generated_on: string;
};

const printStyles = `
@media print {
    @page { size: A4; margin: 14mm; }
    [data-slot="sidebar"], [data-slot="sidebar-inset"] > header { display: none !important; }
    .no-print { display: none !important; }
    .agreement-sheet { box-shadow: none !important; border: 0 !important; margin: 0 !important; max-width: none !important; width: 100% !important; padding: 0 !important; }
    .agreement-section { break-inside: avoid; }
}
.agreement-sheet * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
`;

const textareaClass =
    'min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none';

function percent(basisPoints: number): string {
    return `${basisPoints / 100}%`;
}

function Fact({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div>
            <dt className="text-[11px] font-medium tracking-wide text-zinc-500 uppercase">
                {label}
            </dt>
            <dd className="mt-0.5 text-sm font-medium text-zinc-900">
                {value}
            </dd>
        </div>
    );
}

function Section({
    number,
    title,
    children,
}: {
    number: number;
    title: string;
    children: ReactNode;
}) {
    return (
        <section className="agreement-section break-inside-avoid border-t border-zinc-200 pt-5">
            <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold text-zinc-900">
                <span className="flex size-5 items-center justify-center rounded-full bg-brand-soft text-[11px] font-bold text-brand-soft-foreground">
                    {number}
                </span>
                {title}
            </h2>
            {children}
        </section>
    );
}

function PartyCard({
    role,
    name,
    detail,
}: {
    role: string;
    name: string;
    detail: string;
}) {
    return (
        <div className="rounded-lg border border-zinc-200 p-3">
            <p className="text-[11px] font-medium tracking-wide text-zinc-500 uppercase">
                {role}
            </p>
            <p className="mt-0.5 text-sm font-semibold text-zinc-900">{name}</p>
            <p className="text-xs text-zinc-500">{detail}</p>
        </div>
    );
}

export default function AgreementShow({
    context,
    employer,
    bank,
    funder,
    terms,
    agreement: record,
    generated_on,
}: Props) {
    const t = useT();
    const money = useMoney();
    const { date } = useDates();
    const [rejecting, setRejecting] = useState(false);
    const fileInput = useRef<HTMLInputElement>(null);
    const upload = useForm<{ file: File | null }>({ file: null });

    const isEmployer = context === 'employer';
    const canDecide = isEmployer && record.status === 'pending';
    const statusMeta = agreementStatusMeta[record.status];
    const signedHref = isEmployer
        ? agreement.signed.download.url()
        : downloadAdminSigned.url(employer.id);

    const submitUpload = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        upload.post(agreement.signed.store.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                upload.reset('file');
                if (fileInput.current) {
                    fileInput.current.value = '';
                }
            },
        });
    };

    return (
        <>
            <Head title={t('Salary Advance Programme Agreement')} />
            <style>{printStyles}</style>

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {/* The document */}
                <article className="agreement-sheet mx-auto w-full max-w-[210mm] space-y-6 rounded-xl border border-zinc-200 bg-white p-8 text-zinc-900 shadow-sm md:p-10">
                    {/* Header */}
                    <header className="space-y-4">
                        <div className="flex items-start justify-between gap-4">
                            <div className="flex items-center gap-3">
                                <div className="flex aspect-square size-11 items-center justify-center rounded-xl bg-brand-soft ring-1 ring-brand-soft-foreground/15">
                                    <AppLogoIcon className="size-6 fill-brand-soft-foreground" />
                                </div>
                                <div>
                                    <p className="text-lg font-bold" lang="ar">
                                        ظرف{' '}
                                        <span className="text-zinc-400">
                                            · Zarf
                                        </span>
                                    </p>
                                    <p className="text-xs text-zinc-500">
                                        {t('Salary-linked liquidity platform')}
                                    </p>
                                </div>
                            </div>
                            <StatusPill meta={statusMeta} />
                        </div>

                        <div className="rounded-lg bg-brand-soft/60 p-4">
                            <h1 className="text-xl font-bold text-zinc-900">
                                {t('Salary Advance Programme Agreement')}
                            </h1>
                            <p className="mt-0.5 text-sm text-brand-soft-foreground">
                                {t('Version :v', { v: record.version })}
                            </p>
                        </div>

                        <dl className="grid grid-cols-2 gap-4">
                            <Fact label={t('Employer')} value={employer.name} />
                            <Fact
                                label={t('Generated on')}
                                value={date(generated_on)}
                            />
                        </dl>

                        <p className="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                            {t(
                                'Prototype — not a live financial service. No money moves; bank, funder and payroll steps are simulated.',
                            )}
                        </p>
                    </header>

                    {/* 1. Parties */}
                    <Section number={1} title={t('Parties')}>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <PartyCard
                                role={t('Technology platform')}
                                name="AWJ Ventures"
                                detail={t('Technology provider only')}
                            />
                            <PartyCard
                                role={t('Licensed bank')}
                                name={bank ?? '—'}
                                detail={t('Moves the money')}
                            />
                            <PartyCard
                                role={t('Approved funder')}
                                name={funder ?? '—'}
                                detail={t('Holds the receivable')}
                            />
                            <PartyCard
                                role={t('Employer')}
                                name={employer.name}
                                detail={t('CR :number', {
                                    number: employer.cr_number,
                                })}
                            />
                        </div>
                    </Section>

                    {/* 2. Scope */}
                    <Section number={2} title={t('Scope of the programme')}>
                        <p className="text-sm leading-6 text-zinc-700">
                            {t(
                                'This agreement covers salary advances offered to the employees of the employer named above, on the programme terms in force for that employer.',
                            )}
                        </p>
                    </Section>

                    {/* 3. Roles */}
                    <Section number={3} title={t('Roles of the parties')}>
                        <p className="text-sm leading-6 text-zinc-700">
                            {t(
                                'The platform runs eligibility checks, disclosures and the programme record. A licensed bank moves the money, and an approved funder holds the receivable.',
                            )}
                        </p>
                    </Section>

                    {/* 4. Fee and limits */}
                    <Section number={4} title={t('Fee and limits in force')}>
                        <dl className="mb-3 grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <Fact
                                label={t('Fixed fee')}
                                value={money(terms.flat_fee)}
                            />
                            <Fact
                                label={t('Limit ratio')}
                                value={percent(terms.limit_ratio_bps)}
                            />
                            <Fact
                                label={t('Maximum limit')}
                                value={money(terms.max_limit)}
                            />
                            <Fact
                                label={t('Minimum advance')}
                                value={money(terms.min_advance)}
                            />
                        </dl>
                        <p className="text-sm leading-6 text-zinc-700">
                            {t(
                                'Each advance carries the fixed fee shown above. The limit is a share of each employee verified net salary, capped at the maximum limit.',
                            )}
                        </p>
                    </Section>

                    {/* 5. Collection */}
                    <Section number={5} title={t('Collection by direct debit')}>
                        <p className="text-sm leading-6 text-zinc-700">
                            {t(
                                'Repayment is collected from the employee salary by direct debit, with up to :count attempts, retried every :days days.',
                                {
                                    count: terms.collection_max_attempts,
                                    days: terms.collection_retry_days,
                                },
                            )}
                        </p>
                    </Section>

                    {/* 6. Data protection */}
                    <Section number={6} title={t('Data protection')}>
                        <p className="text-sm leading-6 text-zinc-700">
                            {t(
                                'Personal data is used only for eligibility, disbursement and collection. Civil IDs and IBANs are masked in every record.',
                            )}
                        </p>
                    </Section>

                    {/* 7. Changes */}
                    <Section number={7} title={t('Changes to terms')}>
                        <p className="text-sm leading-6 text-zinc-700">
                            {t(
                                'Terms may change. Any change is shown in the portal before it applies, and the agreement version is updated.',
                            )}
                        </p>
                    </Section>

                    {/* 8. Acceptance */}
                    <Section number={8} title={t('Acceptance')}>
                        <p className="text-sm leading-6 text-zinc-700">
                            {t(
                                'The employer accepts or rejects this agreement in the employer portal, and may attach a signed copy for the record.',
                            )}
                        </p>

                        <dl className="mt-3 grid gap-4 sm:grid-cols-2">
                            <Fact
                                label={t('Decided on')}
                                value={
                                    record.decided_at
                                        ? date(record.decided_at)
                                        : t('Awaiting the employer decision')
                                }
                            />
                            {record.status === 'rejected' &&
                                record.reject_reason && (
                                    <Fact
                                        label={t('Reason for rejection')}
                                        value={record.reject_reason}
                                    />
                                )}
                        </dl>
                    </Section>

                    {/* 9. Signed copy */}
                    {record.signed && (
                        <Section number={9} title={t('Signed copy')}>
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <dl className="grid gap-4 sm:grid-cols-2">
                                    <Fact
                                        label={t('File')}
                                        value={
                                            <span dir="ltr">
                                                {record.signed.name}
                                            </span>
                                        }
                                    />
                                    <Fact
                                        label={t('Uploaded')}
                                        value={
                                            record.signed.uploaded_at
                                                ? date(
                                                      record.signed.uploaded_at,
                                                  )
                                                : '—'
                                        }
                                    />
                                </dl>
                                <Button asChild variant="outline" size="sm">
                                    <a href={signedHref} className="no-print">
                                        <Download />
                                        {t('Download')}
                                    </a>
                                </Button>
                            </div>
                        </Section>
                    )}
                </article>

                {/* Employer decision, screen only */}
                {canDecide && (
                    <section className="no-print mx-auto w-full max-w-[210mm] space-y-6 rounded-xl border bg-card p-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="font-medium">
                                    {t('Your decision')}
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    {t(
                                        'Accept the agreement to start offering salary advances, or reject it with a reason.',
                                    )}
                                </p>
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        setRejecting((open) => !open)
                                    }
                                >
                                    <X />
                                    {t('Reject agreement')}
                                </Button>
                                <Form
                                    {...agreement.accept.form()}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing ? (
                                                <Spinner />
                                            ) : (
                                                <Check />
                                            )}
                                            {t('Accept agreement')}
                                        </Button>
                                    )}
                                </Form>
                            </div>
                        </div>

                        {rejecting && (
                            <Form
                                {...agreement.reject.form()}
                                options={{ preserveScroll: true }}
                                className="grid gap-3"
                            >
                                {({ processing, errors }) => (
                                    <Field
                                        label={t('Reason for rejection')}
                                        htmlFor="reject_reason"
                                        error={errors.reason}
                                    >
                                        <textarea
                                            id="reject_reason"
                                            name="reason"
                                            required
                                            maxLength={255}
                                            className={textareaClass}
                                        />
                                        <div>
                                            <Button
                                                type="submit"
                                                variant="destructive"
                                                disabled={processing}
                                            >
                                                {processing ? (
                                                    <Spinner />
                                                ) : (
                                                    <X />
                                                )}
                                                {t('Confirm rejection')}
                                            </Button>
                                        </div>
                                    </Field>
                                )}
                            </Form>
                        )}

                        <form
                            onSubmit={submitUpload}
                            className="grid gap-4 border-t pt-4 sm:grid-cols-[1fr_auto] sm:items-end"
                        >
                            <Field
                                label={t('Upload signed copy')}
                                htmlFor="signed_file"
                                error={upload.errors.file}
                                hint={t('PDF up to 5 MB.')}
                            >
                                <Input
                                    ref={fileInput}
                                    id="signed_file"
                                    type="file"
                                    name="file"
                                    accept=".pdf,application/pdf"
                                    required
                                    onChange={(event) =>
                                        upload.setData(
                                            'file',
                                            event.target.files?.[0] ?? null,
                                        )
                                    }
                                />
                            </Field>
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={
                                    upload.processing ||
                                    upload.data.file === null
                                }
                            >
                                {upload.processing ? <Spinner /> : <FileUp />}
                                {t('Upload')}
                            </Button>
                        </form>
                        <InputError message={upload.errors.file} />
                    </section>
                )}

                <div className="no-print mx-auto flex w-full max-w-[210mm] items-center justify-between gap-2">
                    <p className="flex items-center gap-2 text-xs text-muted-foreground">
                        <ShieldCheck className="size-4" />
                        {t('Prototype · not a live financial service')}
                    </p>
                    <Button onClick={() => window.print()}>
                        <Printer />
                        {t('Print / Save as PDF')}
                    </Button>
                </div>
            </div>
        </>
    );
}

AgreementShow.layout = {
    breadcrumbs: [{ title: tKey('Agreement'), href: '#' }],
};
