import { Form, Head } from '@inertiajs/react';
import { Pencil, Plus, UserPlus } from 'lucide-react';
import { useState } from 'react';
import { Field } from '@/components/portal/field';
import { PageHeader } from '@/components/portal/page-header';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { tKey, useT } from '@/lib/i18n';
import { useMoney } from '@/lib/money';
import { index, store, update } from '@/routes/admin/partners';
import { store as storeViewer } from '@/routes/admin/partners/viewers';

type Partner = {
    id: number;
    type: 'bank' | 'funder';
    name: string;
    code: string;
    brand_color: string | null;
    employers_count: number;
    outstanding: number;
    viewers: { id: number; name: string; email: string }[];
};

export default function Partners({ partners }: { partners: Partner[] }) {
    const t = useT();
    const money = useMoney();

    return (
        <>
            <Head title={t('Partners')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Partners')}
                    description={t(
                        'Licensed banks move the money and hold mandates; funders provide the capital.',
                    )}
                    actions={<PartnerDialog />}
                />

                <div className="grid gap-4 lg:grid-cols-2">
                    {partners.map((partner) => (
                        <section
                            key={partner.id}
                            className="space-y-4 rounded-xl border bg-card p-4"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex items-center gap-3">
                                    <span
                                        className="size-8 rounded-md border"
                                        style={{
                                            backgroundColor:
                                                partner.brand_color ??
                                                undefined,
                                        }}
                                    />
                                    <div>
                                        <h2 className="font-medium">
                                            {partner.name}
                                        </h2>
                                        <p className="text-xs text-muted-foreground">
                                            {t(
                                                partner.type === 'bank'
                                                    ? 'Bank'
                                                    : 'Funder',
                                            )}{' '}
                                            · {partner.code}
                                        </p>
                                    </div>
                                </div>
                                <PartnerDialog partner={partner} />
                            </div>

                            <dl className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        {t('Employers')}
                                    </dt>
                                    <dd className="font-medium tabular-nums">
                                        {partner.employers_count}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        {t('Outstanding')}
                                    </dt>
                                    <dd className="font-medium tabular-nums">
                                        {money(partner.outstanding)}
                                    </dd>
                                </div>
                            </dl>

                            <div className="space-y-2 border-t pt-4">
                                <h3 className="text-sm font-medium">
                                    {t('Portal viewers')}
                                </h3>
                                {partner.viewers.map((viewer) => (
                                    <p key={viewer.id} className="text-sm">
                                        {viewer.name}{' '}
                                        <span
                                            className="text-muted-foreground"
                                            dir="ltr"
                                        >
                                            {viewer.email}
                                        </span>
                                    </p>
                                ))}
                                <Form
                                    {...storeViewer.form(partner.id)}
                                    resetOnSuccess
                                    className="flex flex-wrap gap-2"
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <Input
                                                name="name"
                                                placeholder={t('Name')}
                                                className="w-36"
                                                required
                                            />
                                            <Input
                                                name="email"
                                                type="email"
                                                placeholder={t('Email address')}
                                                className="w-52"
                                                required
                                                dir="ltr"
                                            />
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                size="sm"
                                                disabled={processing}
                                            >
                                                <UserPlus />
                                                {t('Invite')}
                                            </Button>
                                            {(errors.email || errors.name) && (
                                                <p className="w-full text-sm text-red-600">
                                                    {errors.email ??
                                                        errors.name}
                                                </p>
                                            )}
                                        </>
                                    )}
                                </Form>
                            </div>
                        </section>
                    ))}
                </div>
            </div>
        </>
    );
}

function PartnerDialog({ partner }: { partner?: Partner }) {
    const t = useT();
    const [open, setOpen] = useState(false);
    const action = partner ? update.form(partner.id) : store.form();

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {partner ? (
                    <Button variant="ghost" size="icon" aria-label={t('Edit')}>
                        <Pencil />
                    </Button>
                ) : (
                    <Button>
                        <Plus />
                        {t('New partner')}
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {partner ? partner.name : t('New partner')}
                    </DialogTitle>
                </DialogHeader>
                <Form
                    {...action}
                    className="grid gap-4"
                    onSuccess={() => setOpen(false)}
                >
                    {({ processing, errors }) => (
                        <>
                            {!partner && (
                                <Field label={t('Type')} error={errors.type}>
                                    <Select name="type" defaultValue="bank">
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="bank">
                                                {t('Bank')}
                                            </SelectItem>
                                            <SelectItem value="funder">
                                                {t('Funder')}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </Field>
                            )}
                            <Field
                                label={t('Name')}
                                htmlFor="partner_name"
                                error={errors.name}
                            >
                                <Input
                                    id="partner_name"
                                    name="name"
                                    defaultValue={partner?.name}
                                    required
                                />
                            </Field>
                            <Field
                                label={t('Code')}
                                htmlFor="code"
                                error={errors.code}
                            >
                                <Input
                                    id="code"
                                    name="code"
                                    defaultValue={partner?.code}
                                    required
                                    dir="ltr"
                                />
                            </Field>
                            <Field
                                label={t('Brand colour')}
                                htmlFor="brand_color"
                                hint={t(
                                    "Used on employees' home screen for white-label demos.",
                                )}
                                error={errors.brand_color}
                            >
                                <Input
                                    id="brand_color"
                                    name="brand_color"
                                    type="color"
                                    defaultValue={
                                        partner?.brand_color ?? '#0f766e'
                                    }
                                    className="h-10 w-20 p-1"
                                />
                            </Field>
                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {t('Save')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

Partners.layout = {
    breadcrumbs: [{ title: tKey('Partners'), href: index() }],
};
