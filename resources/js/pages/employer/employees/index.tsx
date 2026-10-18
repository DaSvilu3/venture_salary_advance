import { Form, Head, Link, router } from '@inertiajs/react';
import { FileUp, Search, UserPlus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/portal/page-header';
import { StatusPill } from '@/components/portal/status-pill';
import { EmptyRow, TD, TH, THead, TR, Table } from '@/components/portal/table';
import { SimplePagination } from '@/components/simple-pagination';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { tKey, useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { employmentStatusMeta, enrolmentStatusMeta } from '@/lib/roster-status';
import { cn } from '@/lib/utils';
import {
    importMethod as importRoster,
    index,
    show,
    store,
} from '@/routes/employer/employees';
import type { LaravelPaginator } from '@/types';

export type RosterEmployee = {
    id: number;
    name: string;
    staff_no: string;
    email: string;
    net_salary: number;
    employment_status: string;
    enrolment_status: string;
    has_account: boolean;
    invited_at: string | null;
    invitation_url: string | null;
};

type Props = {
    employees: LaravelPaginator<RosterEmployee>;
    filters: { search: string; status: string };
    importResult: {
        added: number;
        errors: { line: number; messages: string[] }[];
    } | null;
};

const STATUS_FILTERS = [
    '',
    'invited',
    'pending_approval',
    'enrolled',
    'rejected',
];

export default function Employees({ employees, filters, importResult }: Props) {
    const t = useT();
    const money = useMoney();
    const [search, setSearch] = useState(filters.search);

    const applyFilters = (next: Partial<Props['filters']>) => {
        router.get(
            index.url(),
            { ...filters, search, ...next },
            { preserveState: true, replace: true },
        );
    };

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        applyFilters({ search });
    };

    return (
        <>
            <Head title={t('Employees')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Employees')}
                    description={t(
                        'Your roster. Staff join by invitation and you approve their enrolment.',
                    )}
                    actions={
                        <>
                            <ImportDialog />
                            <AddEmployeeDialog />
                        </>
                    }
                />

                {importResult && importResult.errors.length > 0 && (
                    <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
                        <p className="font-medium">
                            {t('Some rows were not imported:')}
                        </p>
                        <ul className="mt-2 list-disc space-y-1 ps-5">
                            {importResult.errors.map((error) => (
                                <li key={error.line}>
                                    {t('Line :line', { line: error.line })}:{' '}
                                    {error.messages.join(' ')}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                <div className="flex flex-wrap items-center gap-2">
                    <form
                        onSubmit={submitSearch}
                        className="relative w-full max-w-xs"
                    >
                        <Search className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Search name or staff number')}
                            className="ps-9"
                        />
                    </form>
                    <div className="flex flex-wrap gap-1">
                        {STATUS_FILTERS.map((status) => (
                            <Button
                                key={status || 'all'}
                                size="sm"
                                variant={
                                    filters.status === status
                                        ? 'secondary'
                                        : 'ghost'
                                }
                                onClick={() => applyFilters({ status })}
                            >
                                {status === ''
                                    ? t('All')
                                    : t(enrolmentStatusMeta[status].label)}
                            </Button>
                        ))}
                    </div>
                </div>

                <Table>
                    <THead>
                        <tr>
                            <TH>{t('Name')}</TH>
                            <TH>{t('Staff number')}</TH>
                            <TH className="text-end">{t('Net salary')}</TH>
                            <TH>{t('Employment')}</TH>
                            <TH>{t('Enrolment')}</TH>
                        </tr>
                    </THead>
                    <tbody>
                        {employees.data.length === 0 && (
                            <EmptyRow colSpan={5}>
                                {t('No employees match.')}
                            </EmptyRow>
                        )}
                        {employees.data.map((employee) => (
                            <TR key={employee.id}>
                                <TD>
                                    <Link
                                        href={show(employee.id)}
                                        className="font-medium hover:underline"
                                    >
                                        {employee.name}
                                    </Link>
                                    <p className="text-xs text-muted-foreground">
                                        <bdi>{employee.email}</bdi>
                                    </p>
                                </TD>
                                <TD className="tabular-nums">
                                    {employee.staff_no}
                                </TD>
                                <TD className="text-end tabular-nums">
                                    {money(employee.net_salary)}
                                </TD>
                                <TD>
                                    <StatusPill
                                        meta={
                                            employmentStatusMeta[
                                                employee.employment_status
                                            ]
                                        }
                                    />
                                </TD>
                                <TD>
                                    <StatusPill
                                        meta={
                                            enrolmentStatusMeta[
                                                employee.enrolment_status
                                            ]
                                        }
                                    />
                                </TD>
                            </TR>
                        ))}
                    </tbody>
                </Table>

                <SimplePagination page={employees} />
            </div>
        </>
    );
}

function AddEmployeeDialog() {
    const t = useT();
    const [open, setOpen] = useState(false);

    const fields: {
        name: string;
        label: string;
        type?: string;
        ltr?: boolean;
        optional?: boolean;
    }[] = [
        { name: 'name', label: tKey('Full name') },
        { name: 'staff_no', label: tKey('Staff number'), ltr: true },
        { name: 'civil_id', label: tKey('Civil ID'), ltr: true },
        {
            name: 'email',
            label: tKey('Email address'),
            type: 'email',
            ltr: true,
        },
        {
            name: 'phone',
            label: tKey('Mobile (optional)'),
            ltr: true,
            optional: true,
        },
        {
            name: 'net_salary',
            label: tKey('Net monthly salary (OMR)'),
            type: 'number',
            ltr: true,
        },
        {
            name: 'employment_start_date',
            label: tKey('Start date'),
            type: 'date',
            ltr: true,
        },
    ];

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button data-test="add-employee-button">
                    <UserPlus />
                    {t('Add employee')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('Add employee')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'They will get an email invitation to set up their account.',
                        )}
                    </DialogDescription>
                </DialogHeader>
                <Form {...store.form()} className="grid gap-4 sm:grid-cols-2">
                    {({ processing, errors }) => (
                        <>
                            {fields.map((field) => (
                                <div
                                    key={field.name}
                                    className={cn(
                                        'grid gap-2',
                                        field.name === 'name' &&
                                            'sm:col-span-2',
                                    )}
                                >
                                    <Label htmlFor={field.name}>
                                        {t(field.label)}
                                    </Label>
                                    <Input
                                        id={field.name}
                                        name={field.name}
                                        type={field.type ?? 'text'}
                                        step={
                                            field.type === 'number'
                                                ? '0.001'
                                                : undefined
                                        }
                                        required={!field.optional}
                                        dir={field.ltr ? 'ltr' : undefined}
                                    />
                                    <InputError message={errors[field.name]} />
                                </div>
                            ))}
                            <div className="flex justify-end sm:col-span-2">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {t('Add and invite')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function ImportDialog() {
    const t = useT();
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline">
                    <FileUp />
                    {t('Import CSV')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('Import employees from CSV')}</DialogTitle>
                    <DialogDescription>
                        {t('The first row must be these column names:')}
                    </DialogDescription>
                </DialogHeader>
                <code
                    className="block rounded-md bg-muted p-3 text-xs break-all"
                    dir="ltr"
                >
                    staff_no,name,civil_id,email,phone,net_salary,employment_start_date
                </code>
                <p className="text-xs text-muted-foreground">
                    {t(
                        'Salaries are net monthly amounts in OMR. Dates use YYYY-MM-DD. Every new employee is invited by email.',
                    )}
                </p>
                <Form
                    {...importRoster.form()}
                    className="space-y-3"
                    onSuccess={() => setOpen(false)}
                >
                    {({ processing, errors }) => (
                        <>
                            <Input
                                type="file"
                                name="file"
                                accept=".csv,text/csv"
                                required
                            />
                            <InputError message={errors.file} />
                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {t('Import')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

Employees.layout = {
    breadcrumbs: [{ title: tKey('Employees'), href: index() }],
};
