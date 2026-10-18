import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/portal/page-header';
import { StatusPill } from '@/components/portal/status-pill';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { tKey, useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { employerStatusMeta } from '@/lib/roster-status';
import { edit, update } from '@/routes/employer/settings';

type Props = {
    employer: {
        name: string;
        cr_number: string;
        status: string;
        payday_day: number;
        subscription_plan: string | null;
        bank: string | null;
        funder: string | null;
    };
    terms: {
        limit_ratio_bps: number;
        max_limit: number;
        min_advance: number;
        flat_fee: number;
        min_tenure_months: number;
        cutoff_days_before_payday: number;
        max_draws_per_cycle: number;
    };
};

export default function Settings({ employer, terms }: Props) {
    const t = useT();
    const money = useMoney();

    return (
        <>
            <Head title={t('Company settings')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Company settings')}
                    actions={
                        <StatusPill
                            meta={employerStatusMeta[employer.status]}
                        />
                    }
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    <section className="space-y-4 rounded-xl border bg-card p-4">
                        <h2 className="font-medium">{t('Company')}</h2>
                        <dl className="grid grid-cols-2 gap-4 text-sm">
                            <Item label={t('Name')} value={employer.name} />
                            <Item
                                label={t('Commercial registration')}
                                value={employer.cr_number}
                            />
                            <Item
                                label={t('Bank partner')}
                                value={employer.bank ?? '—'}
                            />
                            <Item
                                label={t('Funding partner')}
                                value={employer.funder ?? '—'}
                            />
                            <Item
                                label={t('Plan')}
                                value={employer.subscription_plan ?? '—'}
                            />
                        </dl>

                        <Form
                            {...update.form()}
                            className="space-y-2 border-t pt-4"
                            options={{ preserveScroll: true }}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <Label htmlFor="payday_day">
                                        {t('Payday (day of the month)')}
                                    </Label>
                                    <div className="flex gap-2">
                                        <Input
                                            id="payday_day"
                                            name="payday_day"
                                            type="number"
                                            min={1}
                                            max={31}
                                            defaultValue={employer.payday_day}
                                            className="w-24"
                                            dir="ltr"
                                        />
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            {t('Save')}
                                        </Button>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {t(
                                            'Advances are collected on this day. In shorter months the last day of the month is used.',
                                        )}
                                    </p>
                                    <InputError message={errors.payday_day} />
                                </>
                            )}
                        </Form>
                    </section>

                    <section className="space-y-4 rounded-xl border bg-card p-4">
                        <div>
                            <h2 className="font-medium">
                                {t('Program terms')}
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {t(
                                    'Set by the platform with the bank and funder. Contact us to change them.',
                                )}
                            </p>
                        </div>
                        <dl className="grid grid-cols-2 gap-4 text-sm">
                            <Item
                                label={t('Limit')}
                                value={t(
                                    ':percent% of net salary, up to :cap',
                                    {
                                        percent: terms.limit_ratio_bps / 100,
                                        cap: money(terms.max_limit),
                                    },
                                )}
                            />
                            <Item
                                label={t('Smallest advance')}
                                value={money(terms.min_advance)}
                            />
                            <Item
                                label={t('Fee per advance')}
                                value={money(terms.flat_fee)}
                            />
                            <Item
                                label={t('Advances per pay cycle')}
                                value={t('Up to :count', {
                                    count: terms.max_draws_per_cycle,
                                })}
                            />
                            <Item
                                label={t('Minimum time in the job')}
                                value={t(':count months', {
                                    count: terms.min_tenure_months,
                                })}
                            />
                            <Item
                                label={t('Requests close')}
                                value={t(':count days before payday', {
                                    count: terms.cutoff_days_before_payday,
                                })}
                            />
                        </dl>
                    </section>
                </div>
            </div>
        </>
    );
}

function Item({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="mt-0.5 font-medium">{value}</dd>
        </div>
    );
}

Settings.layout = {
    breadcrumbs: [{ title: tKey('Company settings'), href: edit() }],
};
