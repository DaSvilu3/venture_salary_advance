import { Form, Head, Link } from '@inertiajs/react';
import { Check, Clock3, Landmark, PenLine } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatIban, useDates } from '@/lib/format';
import { useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { cn } from '@/lib/utils';
import { home } from '@/routes/app';
import { bankAccount, mandate as mandateRoute } from '@/routes/app/onboarding';

type Props = {
    employer: string;
    bank: string | null;
    iban: string | null;
    mandate: {
        reference: string;
        max_amount: number;
        signed_at: string | null;
    } | null;
    enrolmentStatus: string;
    salaryVerified: boolean;
};

export default function Onboarding({
    employer,
    bank,
    iban,
    mandate,
    enrolmentStatus,
    salaryVerified,
}: Props) {
    const t = useT();
    const money = useMoney();
    const { date } = useDates();
    const [editingIban, setEditingIban] = useState(iban === null);
    const enrolled = enrolmentStatus === 'enrolled';

    return (
        <>
            <Head title={t('Get set up')} />

            <div className="space-y-1">
                <h1 className="text-xl font-semibold">{t('Get set up')}</h1>
                <p className="text-sm text-muted-foreground">
                    {t(
                        'Three steps before your first advance from :employer.',
                        { employer },
                    )}
                </p>
            </div>

            <ol className="mt-6 space-y-4">
                <Step
                    number={1}
                    done={iban !== null && !editingIban}
                    icon={Landmark}
                    title={t('Your bank account')}
                    description={t(
                        'Advances are paid into this account and collected from it on payday.',
                    )}
                >
                    {editingIban ? (
                        <Form
                            {...bankAccount.form()}
                            className="space-y-3"
                            onSuccess={() => setEditingIban(false)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="iban">IBAN</Label>
                                        <Input
                                            id="iban"
                                            name="iban"
                                            defaultValue={iban ?? ''}
                                            placeholder="OM60 0180 0000 1234 5678 901"
                                            autoComplete="off"
                                            dir="ltr"
                                            required
                                        />
                                        <InputError message={errors.iban} />
                                    </div>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        size="sm"
                                    >
                                        {processing && <Spinner />}
                                        {t('Save bank account')}
                                    </Button>
                                </>
                            )}
                        </Form>
                    ) : (
                        iban && (
                            <div className="flex items-center justify-between gap-2 rounded-lg bg-muted/60 px-3 py-2 text-sm">
                                <span dir="ltr" className="font-mono">
                                    {formatIban(iban)}
                                </span>
                                <Button
                                    variant="link"
                                    size="sm"
                                    className="h-auto p-0"
                                    onClick={() => setEditingIban(true)}
                                >
                                    {t('Change')}
                                </Button>
                            </div>
                        )
                    )}
                </Step>

                <Step
                    number={2}
                    done={mandate !== null}
                    icon={PenLine}
                    title={t('Direct-debit mandate')}
                    description={t(
                        'Authorises :bank to collect each advance from your account on payday.',
                        { bank: bank ?? t('the bank') },
                    )}
                >
                    {mandate ? (
                        <p className="text-sm text-muted-foreground">
                            {t('Signed :date · reference :reference', {
                                date: mandate.signed_at
                                    ? date(mandate.signed_at)
                                    : '—',
                                reference: mandate.reference,
                            })}
                        </p>
                    ) : (
                        <Form {...mandateRoute.form()} className="space-y-3">
                            {({ processing, errors }) => (
                                <>
                                    <p className="text-sm">
                                        {t(
                                            'Each collection is limited to :amount and only covers advances you request.',
                                            { amount: money(1_000_000) },
                                        )}
                                    </p>
                                    <InputError message={errors.mandate} />
                                    <Button
                                        type="submit"
                                        size="sm"
                                        disabled={processing || iban === null}
                                        data-test="sign-mandate-button"
                                    >
                                        {processing && <Spinner />}
                                        {t('Sign mandate')}
                                    </Button>
                                </>
                            )}
                        </Form>
                    )}
                </Step>

                <Step
                    number={3}
                    done={enrolled}
                    icon={Clock3}
                    title={t('Approval by your employer')}
                    description={
                        enrolled
                            ? t('You are all set.')
                            : t(
                                  'HR confirms your employment and salary. This usually takes a working day.',
                              )
                    }
                >
                    {mandate && !salaryVerified && (
                        <p className="text-sm text-amber-700 dark:text-amber-400">
                            {t(
                                'We could not match your salary with payroll yet. HR will check it.',
                            )}
                        </p>
                    )}
                    {enrolled && (
                        <Button asChild size="sm">
                            <Link href={home()}>{t('Go to home')}</Link>
                        </Button>
                    )}
                </Step>
            </ol>
        </>
    );
}

function Step({
    number,
    done,
    icon: Icon,
    title,
    description,
    children,
}: {
    number: number;
    done: boolean;
    icon: typeof Landmark;
    title: string;
    description: string;
    children?: ReactNode;
}) {
    return (
        <li className="rounded-xl border bg-card p-4">
            <div className="flex gap-3">
                <span
                    className={cn(
                        'flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                        done
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                            : 'bg-muted text-muted-foreground',
                    )}
                    aria-label={String(number)}
                >
                    {done ? (
                        <Check className="size-4" />
                    ) : (
                        <Icon className="size-4" />
                    )}
                </span>
                <div className="min-w-0 flex-1 space-y-3">
                    <div>
                        <h2 className="font-medium">{title}</h2>
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    {children}
                </div>
            </div>
        </li>
    );
}
