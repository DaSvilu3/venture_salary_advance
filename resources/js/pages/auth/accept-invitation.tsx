import { Form, Head } from '@inertiajs/react';
import { BadgeCheck } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { tKey, useT } from '@/lib/i18n';
import { accept } from '@/routes/invitation';

type Props = {
    token: string;
    name: string;
    email: string;
    employer: string;
    passwordRules: string;
};

export default function AcceptInvitation({
    token,
    name,
    email,
    employer,
    passwordRules,
}: Props) {
    const t = useT();

    return (
        <>
            <Head title={t('Accept your invitation')} />

            <div className="mb-2 flex items-start gap-3 rounded-2xl bg-brand-soft/60 p-4 text-sm">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-card text-brand shadow-sm ring-1 ring-border/60">
                    <BadgeCheck className="size-4" />
                </span>
                <div>
                    <p>
                        {t(':employer has invited you to salary advances.', {
                            employer,
                        })}
                    </p>
                    <p className="mt-1 font-medium">{name}</p>
                    <p className="text-muted-foreground" dir="ltr">
                        {email}
                    </p>
                </div>
            </div>

            <Form
                {...accept.form(token)}
                resetOnSuccess={['password', 'password_confirmation']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="civil_id_last4">
                                {t('Last 4 digits of your civil ID')}
                            </Label>
                            <Input
                                id="civil_id_last4"
                                name="civil_id_last4"
                                inputMode="numeric"
                                maxLength={4}
                                autoComplete="off"
                                required
                                autoFocus
                                dir="ltr"
                            />
                            <InputError message={errors.civil_id_last4} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {t('Choose a password')}
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                autoComplete="new-password"
                                passwordrules={passwordRules}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                {t('Confirm password')}
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                autoComplete="new-password"
                            />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                            data-test="accept-invitation-button"
                        >
                            {processing && <Spinner />}
                            {t('Create my account')}
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

AcceptInvitation.layout = {
    title: tKey('Welcome aboard'),
    description: tKey('Confirm who you are and choose a password.'),
};
