import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { Field } from '@/components/portal/field';
import { PageHeader } from '@/components/portal/page-header';
import { PartnerSelect } from '@/components/portal/partner-select';
import { StatusPill } from '@/components/portal/status-pill';
import { EmptyRow, TD, TH, THead, TR, Table } from '@/components/portal/table';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { tKey, useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { employerStatusMeta } from '@/lib/roster-status';
import { index, show, store } from '@/routes/admin/employers';

export type PartnerOptions = {
    banks: { id: number; name: string }[];
    funders: { id: number; name: string }[];
};

type Props = {
    employers: {
        id: number;
        name: string;
        cr_number: string;
        status: string;
        payday_day: number;
        bank: string | null;
        funder: string | null;
        employees_count: number;
        enrolled_count: number;
        outstanding: number;
    }[];
    partners: PartnerOptions;
};

export default function Employers({ employers, partners }: Props) {
    const t = useT();
    const money = useMoney();

    return (
        <>
            <Head title={t('Employers')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Employers')}
                    description={t(
                        'Companies offering salary advances to their staff.',
                    )}
                    actions={<CreateEmployerDialog partners={partners} />}
                />

                <Table>
                    <THead>
                        <tr>
                            <TH>{t('Name')}</TH>
                            <TH>{t('Status')}</TH>
                            <TH>{t('Payday')}</TH>
                            <TH>{t('Bank partner')}</TH>
                            <TH className="text-end">{t('Enrolled')}</TH>
                            <TH className="text-end">{t('Outstanding')}</TH>
                        </tr>
                    </THead>
                    <tbody>
                        {employers.length === 0 && (
                            <EmptyRow colSpan={6}>
                                {t('No employers yet.')}
                            </EmptyRow>
                        )}
                        {employers.map((employer) => (
                            <TR key={employer.id}>
                                <TD>
                                    <Link
                                        href={show(employer.id)}
                                        className="font-medium hover:underline"
                                    >
                                        {employer.name}
                                    </Link>
                                    <p className="text-xs text-muted-foreground">
                                        {t('CR :number', {
                                            number: employer.cr_number,
                                        })}
                                    </p>
                                </TD>
                                <TD>
                                    <StatusPill
                                        meta={
                                            employerStatusMeta[employer.status]
                                        }
                                    />
                                </TD>
                                <TD className="tabular-nums">
                                    {employer.payday_day}
                                </TD>
                                <TD className="text-muted-foreground">
                                    {employer.bank ?? '—'}
                                </TD>
                                <TD className="text-end tabular-nums">
                                    {employer.enrolled_count} /{' '}
                                    {employer.employees_count}
                                </TD>
                                <TD className="text-end tabular-nums">
                                    {money(employer.outstanding)}
                                </TD>
                            </TR>
                        ))}
                    </tbody>
                </Table>
            </div>
        </>
    );
}

function CreateEmployerDialog({ partners }: { partners: PartnerOptions }) {
    const t = useT();
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>
                    <Plus />
                    {t('New employer')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('New employer')}</DialogTitle>
                </DialogHeader>
                <Form {...store.form()} className="grid gap-4 sm:grid-cols-2">
                    {({ processing, errors }) => (
                        <>
                            <Field
                                label={t('Name')}
                                htmlFor="name"
                                error={errors.name}
                                className="sm:col-span-2"
                            >
                                <Input id="name" name="name" required />
                            </Field>
                            <Field
                                label={t('Commercial registration')}
                                htmlFor="cr_number"
                                error={errors.cr_number}
                            >
                                <Input
                                    id="cr_number"
                                    name="cr_number"
                                    required
                                    dir="ltr"
                                />
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
                                    defaultValue={25}
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
                                />
                            </Field>
                            <Field
                                label={t('Funding partner')}
                                error={errors.funder_partner_id}
                            >
                                <PartnerSelect
                                    name="funder_partner_id"
                                    options={partners.funders}
                                />
                            </Field>
                            <div className="flex justify-end sm:col-span-2">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {t('Create')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

Employers.layout = {
    breadcrumbs: [{ title: tKey('Employers'), href: index() }],
};
