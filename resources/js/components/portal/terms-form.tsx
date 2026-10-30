import { Form } from '@inertiajs/react';
import type { RouteFormDefinition } from '@/wayfinder';
import { Field } from '@/components/portal/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { tKey, useT } from '@/lib/i18n';

export type TermsValues = {
    limit_ratio_percent: string;
    max_limit: string;
    min_advance: string;
    flat_fee: string;
    min_tenure_months: number;
    cutoff_days_before_payday: number;
    max_draws_per_cycle: number;
    collection_max_attempts: number;
    collection_retry_days: number;
    collection_offset_days: number;
    disbursement_sla_hours: number;
    auto_approve: '1' | '0';
};

const FIELDS: {
    name: keyof TermsValues;
    label: string;
    hint?: string;
    step: string;
}[] = [
    {
        name: 'limit_ratio_percent',
        label: tKey('Limit (% of net salary)'),
        hint: tKey('18.75% gives OMR 150 on an OMR 800 salary.'),
        step: '0.01',
    },
    { name: 'max_limit', label: tKey('Maximum limit (OMR)'), step: '0.001' },
    {
        name: 'min_advance',
        label: tKey('Smallest advance (OMR)'),
        step: '0.001',
    },
    {
        name: 'flat_fee',
        label: tKey('Fee per advance (OMR)'),
        hint: tKey('Placeholder until pricing is agreed with the regulator.'),
        step: '0.001',
    },
    {
        name: 'min_tenure_months',
        label: tKey('Minimum time in the job (months)'),
        step: '1',
    },
    {
        name: 'cutoff_days_before_payday',
        label: tKey('Requests close (days before payday)'),
        step: '1',
    },
    {
        name: 'max_draws_per_cycle',
        label: tKey('Advances per pay cycle'),
        step: '1',
    },
    {
        name: 'collection_max_attempts',
        label: tKey('Collection attempts before overdue'),
        step: '1',
    },
    {
        name: 'collection_retry_days',
        label: tKey('Days between collection retries'),
        step: '1',
    },
    {
        name: 'collection_offset_days',
        label: tKey('Days after payday before collection'),
        step: '1',
    },
    {
        name: 'disbursement_sla_hours',
        label: tKey('Funding promise (hours)'),
        step: '1',
    },
];

export function TermsForm({
    action,
    values,
    submitLabel,
}: {
    action: RouteFormDefinition<'put'> | RouteFormDefinition<'post'>;
    values: TermsValues;
    submitLabel: string;
}) {
    const t = useT();

    return (
        <Form
            {...action}
            className="grid gap-4 sm:grid-cols-2"
            options={{ preserveScroll: true }}
        >
            {({ processing, errors }) => (
                <>
                    {FIELDS.map((field) => (
                        <Field
                            key={field.name}
                            label={t(field.label)}
                            htmlFor={field.name}
                            hint={field.hint ? t(field.hint) : undefined}
                            error={errors[field.name]}
                        >
                            <Input
                                id={field.name}
                                name={field.name}
                                type="number"
                                step={field.step}
                                min={0}
                                defaultValue={values[field.name]}
                                required
                                dir="ltr"
                            />
                        </Field>
                    ))}
                    <Field
                        label={t('Advance approval')}
                        htmlFor="auto_approve"
                        error={errors.auto_approve}
                    >
                        <select
                            id="auto_approve"
                            name="auto_approve"
                            defaultValue={values.auto_approve}
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        >
                            <option value="1">
                                {t('Approve automatically')}
                            </option>
                            <option value="0">
                                {t('Admin approves each advance')}
                            </option>
                        </select>
                    </Field>
                    <div className="sm:col-span-2">
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {submitLabel}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
