import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CircleAlert } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatIban, useDates } from '@/lib/format';
import { useT } from '@/lib/i18n';
import { baisaToOmr, omrToBaisa, useMoney } from '@/lib/money';
import { cn } from '@/lib/utils';
import { home } from '@/routes/app';
import { store } from '@/routes/app/advances';
import type { Eligibility } from '@/types';

type Props = {
    eligibility: Eligibility;
    disclosure: { id: number; version: string; body: string } | null;
    bank: string | null;
    iban: string | null;
};

export default function CreateAdvance({
    eligibility,
    disclosure,
    bank,
    iban,
}: Props) {
    const t = useT();
    const money = useMoney();
    const { date } = useDates();

    const minimumOmr = baisaToOmr(eligibility.minimum);
    const availableOmr = baisaToOmr(eligibility.available);
    const suggested = Math.min(availableOmr, Math.max(minimumOmr, 50));

    const form = useForm({
        amount: String(suggested),
        disclosure_id: disclosure?.id ?? 0,
        accept_terms: false,
    });

    const amountBaisa = omrToBaisa(Number(form.data.amount) || 0);
    const inRange =
        amountBaisa >= eligibility.minimum &&
        amountBaisa <= eligibility.available;
    const canSubmit =
        eligibility.eligible &&
        disclosure !== null &&
        inRange &&
        form.data.accept_terms;

    const quickAmounts = [0.25, 0.5, 1]
        .map((share) => Math.floor((availableOmr * share) / 5) * 5)
        .filter(
            (amount, index, all) =>
                amount >= minimumOmr && all.indexOf(amount) === index,
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store.url());
    };

    return (
        <>
            <Head title={t('Get an advance')} />

            <Link
                href={home()}
                className="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground"
            >
                <ArrowLeft className="size-4 rtl:rotate-180" />
                {t('Back')}
            </Link>

            <h1 className="text-xl font-semibold">{t('Get an advance')}</h1>
            <p className="mt-1 text-sm text-muted-foreground">
                {t('Up to :amount is available until :date.', {
                    amount: money(eligibility.available),
                    date: date(eligibility.last_request_date),
                })}
            </p>

            {!eligibility.eligible || disclosure === null ? (
                <div className="mt-6 space-y-2 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
                    {disclosure === null && (
                        <p>{t('Advances are temporarily unavailable.')}</p>
                    )}
                    {eligibility.reasons.map((reason) => (
                        <p key={reason.code} className="flex gap-2">
                            <CircleAlert className="mt-0.5 size-4 shrink-0" />
                            {reason.message}
                        </p>
                    ))}
                </div>
            ) : (
                <form onSubmit={submit} className="mt-6 space-y-6">
                    <section className="space-y-3 rounded-xl border bg-card p-4">
                        <Label htmlFor="amount">{t('Amount (OMR)')}</Label>
                        <Input
                            id="amount"
                            type="number"
                            inputMode="decimal"
                            min={minimumOmr}
                            max={availableOmr}
                            step="0.001"
                            value={form.data.amount}
                            onChange={(event) =>
                                form.setData('amount', event.target.value)
                            }
                            className="h-12 text-2xl font-semibold tabular-nums"
                            dir="ltr"
                        />
                        <input
                            type="range"
                            aria-label={t('Amount (OMR)')}
                            min={minimumOmr}
                            max={availableOmr}
                            step={1}
                            value={Number(form.data.amount) || minimumOmr}
                            onChange={(event) =>
                                form.setData('amount', event.target.value)
                            }
                            className="w-full accent-primary"
                        />
                        <div className="flex flex-wrap gap-2">
                            {quickAmounts.map((amount) => (
                                <Button
                                    key={amount}
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className={cn(
                                        Number(form.data.amount) === amount &&
                                            'border-primary',
                                    )}
                                    onClick={() =>
                                        form.setData('amount', String(amount))
                                    }
                                >
                                    {money(omrToBaisa(amount))}
                                </Button>
                            ))}
                        </div>
                        <InputError message={form.errors.amount} />
                    </section>

                    <section className="rounded-xl border bg-card p-4 text-sm">
                        <dl className="space-y-2">
                            <SummaryRow
                                label={t('Paid to your account')}
                                value={money(inRange ? amountBaisa : 0)}
                            />
                            <SummaryRow
                                label={t('Service fee')}
                                value={money(eligibility.fee)}
                            />
                            <div className="border-t pt-2">
                                <SummaryRow
                                    label={t('Collected on :date', {
                                        date: date(eligibility.payday),
                                    })}
                                    value={money(
                                        (inRange ? amountBaisa : 0) +
                                            eligibility.fee,
                                    )}
                                    strong
                                />
                            </div>
                        </dl>
                        {iban && (
                            <p className="mt-3 text-xs text-muted-foreground">
                                {t('To :bank account', { bank: bank ?? '' })}{' '}
                                <span dir="ltr" className="font-mono">
                                    {formatIban(iban)}
                                </span>
                            </p>
                        )}
                    </section>

                    <section className="space-y-3">
                        <h2 className="text-sm font-medium">
                            {t('Terms (version :version)', {
                                version: disclosure.version,
                            })}
                        </h2>
                        <div className="max-h-48 overflow-y-auto rounded-xl border bg-muted/40 p-4 text-sm whitespace-pre-line text-muted-foreground">
                            {disclosure.body}
                        </div>
                        <div className="flex items-start gap-3">
                            <Checkbox
                                id="accept_terms"
                                checked={form.data.accept_terms}
                                onCheckedChange={(checked) =>
                                    form.setData(
                                        'accept_terms',
                                        checked === true,
                                    )
                                }
                            />
                            <Label
                                htmlFor="accept_terms"
                                className="leading-snug font-normal"
                            >
                                {t(
                                    'I have read the terms and agree that the total is collected from my account on payday.',
                                )}
                            </Label>
                        </div>
                        <InputError message={form.errors.accept_terms} />
                    </section>

                    <Button
                        type="submit"
                        className="h-12 w-full text-base"
                        disabled={!canSubmit || form.processing}
                        data-test="confirm-advance-button"
                    >
                        {form.processing && <Spinner />}
                        {t('Confirm :amount', {
                            amount: money(inRange ? amountBaisa : 0),
                        })}
                    </Button>
                </form>
            )}
        </>
    );
}

function SummaryRow({
    label,
    value,
    strong = false,
}: {
    label: string;
    value: string;
    strong?: boolean;
}) {
    return (
        <div className="flex items-center justify-between gap-4">
            <dt className={cn(!strong && 'text-muted-foreground')}>{label}</dt>
            <dd className={cn('tabular-nums', strong && 'font-semibold')}>
                {value}
            </dd>
        </div>
    );
}
