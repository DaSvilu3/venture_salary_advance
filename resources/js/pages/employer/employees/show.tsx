import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Check, Copy, Mail, X } from 'lucide-react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/portal/page-header';
import { StatusPill } from '@/components/portal/status-pill';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useClipboard } from '@/hooks/use-clipboard';
import { useDates } from '@/lib/format';
import { tKey, useT } from '@/lib/i18n';
import { baisaToOmr } from '@/lib/money';
import { employmentStatusMeta, enrolmentStatusMeta } from '@/lib/roster-status';
import {
    approve,
    index,
    invitation,
    reject,
    update,
} from '@/routes/employer/employees';
import type { RosterEmployee } from './index';

type Props = {
    employee: RosterEmployee & {
        phone: string | null;
        civil_id: string;
        employment_start_date: string;
        salary_verified: boolean;
        has_mandate: boolean;
    };
    history: {
        id: number;
        action: string;
        actor: string | null;
        at: string | null;
    }[];
    employmentStatuses: string[];
};

const historyLabels: Record<string, string> = {
    'employee.added': tKey('Added to the roster'),
    'employee.invited': tKey('Invitation sent'),
    'employee.invitation_accepted': tKey('Created their account'),
    'employee.mandate_signed': tKey('Signed the direct-debit mandate'),
    'employee.enrolment_approved': tKey('Enrolment approved'),
    'employee.enrolment_rejected': tKey('Enrolment rejected'),
    'employee.updated': tKey('Details updated'),
};

export default function ShowEmployee({
    employee,
    history,
    employmentStatuses,
}: Props) {
    const t = useT();
    const { dateTime } = useDates();
    const [copiedText, copy] = useClipboard();

    return (
        <>
            <Head title={employee.name} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Link
                    href={index()}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground"
                >
                    <ArrowLeft className="size-4 rtl:rotate-180" />
                    {t('Employees')}
                </Link>

                <PageHeader
                    title={employee.name}
                    description={`${employee.staff_no} · ${t('Civil ID')} ${employee.civil_id}`}
                    actions={
                        <div className="flex gap-2">
                            <StatusPill
                                meta={
                                    employmentStatusMeta[
                                        employee.employment_status
                                    ]
                                }
                            />
                            <StatusPill
                                meta={
                                    enrolmentStatusMeta[
                                        employee.enrolment_status
                                    ]
                                }
                            />
                        </div>
                    }
                />

                {employee.enrolment_status === 'pending_approval' && (
                    <section className="space-y-3 rounded-xl border border-sky-300 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/40">
                        <h2 className="font-medium">{t('Review enrolment')}</h2>
                        <p className="text-sm">
                            {employee.salary_verified
                                ? t(
                                      'Bank account and mandate are set up, and the salary matches payroll. Approving confirms they still work here on these terms.',
                                  )
                                : t(
                                      'Bank account and mandate are set up, but the salary did not match payroll. Approving confirms the salary on this page is correct.',
                                  )}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Form {...approve.form(employee.id)}>
                                {({ processing, errors }) => (
                                    <>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            data-test="approve-enrolment"
                                        >
                                            {processing ? (
                                                <Spinner />
                                            ) : (
                                                <Check />
                                            )}
                                            {t('Approve')}
                                        </Button>
                                        <InputError
                                            message={errors.enrolment}
                                        />
                                    </>
                                )}
                            </Form>
                            <Form {...reject.form(employee.id)}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        <X />
                                        {t('Reject')}
                                    </Button>
                                )}
                            </Form>
                        </div>
                    </section>
                )}

                {!employee.has_account && (
                    <section className="space-y-3 rounded-xl border bg-card p-4">
                        <h2 className="font-medium">{t('Invitation')}</h2>
                        <p className="text-sm text-muted-foreground">
                            {employee.invited_at
                                ? t(
                                      'Invited :date. They have not created their account yet.',
                                      {
                                          date: dateTime(employee.invited_at),
                                      },
                                  )
                                : t('Not invited yet.')}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Form {...invitation.form(employee.id)}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        size="sm"
                                        disabled={processing}
                                    >
                                        <Mail />
                                        {t('Send a new invitation')}
                                    </Button>
                                )}
                            </Form>
                            {employee.invitation_url && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() =>
                                        copy(employee.invitation_url ?? '')
                                    }
                                >
                                    {copiedText === employee.invitation_url ? (
                                        <Check />
                                    ) : (
                                        <Copy />
                                    )}
                                    {t('Copy invitation link')}
                                </Button>
                            )}
                        </div>
                    </section>
                )}

                <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <section className="rounded-xl border bg-card p-4">
                        <h2 className="mb-4 font-medium">
                            {t('Employment details')}
                        </h2>
                        <Form
                            {...update.form(employee.id)}
                            className="grid gap-4 sm:grid-cols-2"
                            options={{ preserveScroll: true }}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <Field
                                        label={t('Full name')}
                                        error={errors.name}
                                        className="sm:col-span-2"
                                    >
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={employee.name}
                                            required
                                        />
                                    </Field>
                                    <Field
                                        label={t('Email address')}
                                        error={errors.email}
                                    >
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            defaultValue={employee.email}
                                            required
                                            dir="ltr"
                                        />
                                    </Field>
                                    <Field
                                        label={t('Mobile (optional)')}
                                        error={errors.phone}
                                    >
                                        <Input
                                            id="phone"
                                            name="phone"
                                            defaultValue={employee.phone ?? ''}
                                            dir="ltr"
                                        />
                                    </Field>
                                    <Field
                                        label={t('Net monthly salary (OMR)')}
                                        error={errors.net_salary}
                                    >
                                        <Input
                                            id="net_salary"
                                            name="net_salary"
                                            type="number"
                                            step="0.001"
                                            defaultValue={baisaToOmr(
                                                employee.net_salary,
                                            )}
                                            required
                                            dir="ltr"
                                        />
                                    </Field>
                                    <Field
                                        label={t('Start date')}
                                        error={errors.employment_start_date}
                                    >
                                        <Input
                                            id="employment_start_date"
                                            name="employment_start_date"
                                            type="date"
                                            defaultValue={
                                                employee.employment_start_date
                                            }
                                            required
                                            dir="ltr"
                                        />
                                    </Field>
                                    <Field
                                        label={t('Employment status')}
                                        error={errors.employment_status}
                                    >
                                        <Select
                                            name="employment_status"
                                            defaultValue={
                                                employee.employment_status
                                            }
                                        >
                                            <SelectTrigger
                                                id="employment_status"
                                                className="w-full"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {employmentStatuses.map(
                                                    (status) => (
                                                        <SelectItem
                                                            key={status}
                                                            value={status}
                                                        >
                                                            {t(
                                                                employmentStatusMeta[
                                                                    status
                                                                ].label,
                                                            )}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <p className="text-xs text-muted-foreground sm:col-span-2">
                                        {t(
                                            'Staff on notice or who have left cannot take new advances. Advances already paid out are still collected on payday.',
                                        )}
                                    </p>
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
                        <h2 className="mb-4 font-medium">{t('History')}</h2>
                        <ol className="space-y-3 text-sm">
                            {history.map((entry) => (
                                <li key={entry.id}>
                                    <p>
                                        {t(
                                            historyLabels[entry.action] ??
                                                entry.action,
                                        )}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {[
                                            entry.at
                                                ? dateTime(entry.at)
                                                : null,
                                            entry.actor,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    </section>
                </div>
            </div>
        </>
    );
}

function Field({
    label,
    error,
    className,
    children,
}: {
    label: string;
    error?: string;
    className?: string;
    children: React.ReactNode;
}) {
    return (
        <div className={`grid gap-2 ${className ?? ''}`}>
            <Label>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

ShowEmployee.layout = {
    breadcrumbs: [{ title: tKey('Employees'), href: index() }],
};
