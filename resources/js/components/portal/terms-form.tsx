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
