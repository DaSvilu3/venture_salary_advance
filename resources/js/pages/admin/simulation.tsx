import { Form, Head } from '@inertiajs/react';
import { CalendarClock, Play, RotateCcw, Trash2, Zap } from 'lucide-react';
import { Field } from '@/components/portal/field';
import { PageHeader } from '@/components/portal/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDates } from '@/lib/format';
import { tKey, useT } from '@/lib/i18n';
import { show } from '@/routes/admin/simulation';
import { reset, update } from '@/routes/admin/simulation/clock';
import { run } from '@/routes/admin/simulation/collections';
import {
    clear,
    store as storeFailure,
} from '@/routes/admin/simulation/failures';

type Props = {
    demoClock: { today: string; real_today: string; is_simulated: boolean };
    employers: { id: number; name: string; next_payday: string }[];
    employees: { id: number; name: string; staff_no: string }[];
    operations: string[];
    failures: {
        operation: string;
        employee_id: number;
        employee: string;
        remaining: number;
    }[];
};

const operationLabels: Record<string, string> = {
    disbursement: tKey('Payout to the employee'),
    collection: tKey('Payday direct debit'),
    funding: tKey('Funding request'),
    salary_verification: tKey('Salary check with payroll'),
};

function addDays(date: string, days: number): string {
    const next = new Date(`${date}T00:00:00Z`);
    next.setUTCDate(next.getUTCDate() + days);

    return next.toISOString().slice(0, 10);
}

export default function Simulation({
    demoClock: clock,
    employers,
    employees,
    operations,
    failures,
}: Props) {
    const t = useT();
    const { date } = useDates();

    return (
        <>
            <Head title={t('Simulation')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Simulation')}
                    description={t(
                        'Demo controls. Nothing here moves real money: every bank, funder and payroll call goes to a fake driver.',
                    )}
                />

                <section className="space-y-4 rounded-xl border bg-card p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <CalendarClock className="size-5 text-muted-foreground" />
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    {t('Demo clock')}
                                </p>
                                <p className="text-lg font-semibold">
                                    {date(clock.today)}
                                </p>
                            </div>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {clock.is_simulated
                                ? t('Real date: :date', {
                                      date: date(clock.real_today),
                                  })
                                : t('Showing the real date.')}
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {[1, 3, 7].map((days) => (
                            <Form
                                key={days}
                                {...update.form()}
                                transform={() => ({
                                    date: addDays(clock.today, days),
                                })}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        size="sm"
                                        disabled={processing}
                                    >
                                        {t('+:days days', { days })}
                                    </Button>
                                )}
                            </Form>
                        ))}
                        {employers.map((employer) => (
                            <Form
                                key={employer.id}
                                {...update.form()}
                                transform={() => ({
                                    date: employer.next_payday,
                                })}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        size="sm"
                                        disabled={processing}
                                    >
                                        {t(':employer payday (:date)', {
                                            employer: employer.name,
                                            date: date(employer.next_payday),
                                        })}
                                    </Button>
                                )}
                            </Form>
                        ))}
                    </div>

                    <div className="flex flex-wrap items-end gap-2">
                        <Form
                            {...update.form()}
                            className="flex items-end gap-2"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <Field
                                        label={t('Set a date')}
                                        htmlFor="date"
                                        error={errors.date}
                                    >
                                        <Input
                                            id="date"
                                            name="date"
                                            type="date"
                                            defaultValue={clock.today}
                                            dir="ltr"
                                        />
                                    </Field>
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        {t('Set')}
                                    </Button>
                                </>
                            )}
                        </Form>
                        {clock.is_simulated && (
                            <Form {...reset.form()}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="ghost"
                                        disabled={processing}
                                    >
                                        <RotateCcw />
                                        {t('Back to today')}
                                    </Button>
                                )}
                            </Form>
                        )}
                    </div>
                </section>

                <section className="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-card p-4">
                    <div>
                        <h2 className="font-medium">
                            {t('Run payday collections')}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {t(
                                'Does what the daily 06:00 job does: collects every advance due on or before the demo date and closes finished pay cycles.',
                            )}
                        </p>
                    </div>
                    <Form {...run.form()}>
                        {({ processing }) => (
                            <Button
                                type="submit"
                                disabled={processing}
                                data-test="run-collections"
                            >
                                <Play />
                                {t('Run now')}
                            </Button>
                        )}
                    </Form>
                </section>

                <section className="space-y-4 rounded-xl border bg-card p-4">
                    <div>
                        <h2 className="font-medium">{t('Force a failure')}</h2>
                        <p className="text-sm text-muted-foreground">
                            {t(
                                "The fake gateway fails the employee's next requests of this kind, then works normally again.",
                            )}
                        </p>
                    </div>
                    <Form
                        {...storeFailure.form()}
                        className="grid gap-3 sm:grid-cols-[2fr_2fr_6rem_auto] sm:items-end"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Field
                                    label={t('Employee')}
                                    htmlFor="employee_id"
                                    error={errors.employee_id}
                                >
                                    {/* Native select: type-to-search over a long list, and its value always submits. */}
                                    <select
                                        id="employee_id"
                                        name="employee_id"
                                        defaultValue=""
                                        required
                                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                    >
                                        <option value="" disabled>
                                            {t('Choose…')}
                                        </option>
                                        {employees.map((employee) => (
                                            <option
                                                key={employee.id}
                                                value={employee.id}
                                            >
                                                {employee.name} ·{' '}
                                                {employee.staff_no}
                                            </option>
                                        ))}
                                    </select>
                                </Field>
                                <Field
                                    label={t('What fails')}
                                    error={errors.operation}
                                >
                                    <Select
                                        name="operation"
                                        defaultValue="collection"
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {operations.map((operation) => (
                                                <SelectItem
                                                    key={operation}
                                                    value={operation}
                                                >
                                                    {t(
                                                        operationLabels[
                                                            operation
                                                        ] ?? operation,
                                                    )}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </Field>
                                <Field
                                    label={t('Times')}
                                    htmlFor="times"
                                    error={errors.times}
                                >
                                    <Input
                                        id="times"
                                        name="times"
                                        type="number"
                                        min={1}
                                        max={5}
                                        defaultValue={1}
                                        dir="ltr"
                                    />
                                </Field>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                >
                                    <Zap />
                                    {t('Force')}
                                </Button>
                            </>
                        )}
                    </Form>

                    {failures.length > 0 && (
                        <div className="space-y-2 border-t pt-4">
                            <ul className="space-y-1 text-sm">
                                {failures.map((failure) => (
                                    <li
                                        key={`${failure.operation}-${failure.employee_id}`}
                                    >
                                        {t(
                                            ':operation for :name · forced failures left: :count',
                                            {
                                                operation: t(
                                                    operationLabels[
                                                        failure.operation
                                                    ] ?? failure.operation,
                                                ),
                                                name: failure.employee,
                                                count: failure.remaining,
                                            },
                                        )}
                                    </li>
                                ))}
                            </ul>
                            <Form {...clear.form()}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="ghost"
                                        size="sm"
                                        disabled={processing}
                                    >
                                        <Trash2 />
                                        {t('Clear all')}
                                    </Button>
                                )}
                            </Form>
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

Simulation.layout = {
    breadcrumbs: [{ title: tKey('Simulation'), href: show() }],
};
