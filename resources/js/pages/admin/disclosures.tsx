import { Form, Head } from '@inertiajs/react';
import { Send } from 'lucide-react';
import { Field } from '@/components/portal/field';
import { PageHeader } from '@/components/portal/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useDates } from '@/lib/format';
import { tKey, useT } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { index, publish, store } from '@/routes/admin/disclosures';

type Disclosure = {
    id: number;
    version: string;
    body_en: string;
    body_ar: string;
    published_at: string | null;
    is_current: boolean;
    advances_count: number;
};

const textareaClass =
    'min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none';

export default function Disclosures({
    disclosures,
}: {
    disclosures: Disclosure[];
}) {
    const t = useT();
    const { dateTime } = useDates();

    return (
        <>
            <Head title={t('Disclosures')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Disclosures')}
                    description={t(
                        'The terms employees accept with each advance. Published versions cannot be edited; publish a new version instead.',
                    )}
                />

                <section className="rounded-xl border bg-card p-4">
                    <h2 className="mb-4 font-medium">
                        {t('Draft a new version')}
                    </h2>
                    <Form
                        {...store.form()}
                        resetOnSuccess
                        className="grid gap-4 lg:grid-cols-2"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Field
                                    label={t('Version')}
                                    htmlFor="version"
                                    error={errors.version}
                                    className="lg:col-span-2"
                                >
                                    <Input
                                        id="version"
                                        name="version"
                                        placeholder="2026.2"
                                        className="max-w-40"
                                        required
                                        dir="ltr"
                                    />
                                </Field>
                                <Field
                                    label={t('English text')}
                                    htmlFor="body_en"
                                    error={errors.body_en}
                                >
                                    <textarea
                                        id="body_en"
                                        name="body_en"
                                        className={textareaClass}
                                        required
                                        dir="ltr"
                                    />
                                </Field>
                                <Field
                                    label={t('Arabic text')}
                                    htmlFor="body_ar"
                                    error={errors.body_ar}
                                >
                                    <textarea
                                        id="body_ar"
                                        name="body_ar"
                                        className={textareaClass}
                                        required
                                        dir="rtl"
                                        lang="ar"
                                    />
                                </Field>
                                <div>
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        {processing && <Spinner />}
                                        {t('Save draft')}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </section>

                <div className="space-y-4">
                    {disclosures.map((disclosure) => (
                        <section
                            key={disclosure.id}
                            className={cn(
                                'space-y-3 rounded-xl border bg-card p-4',
                                disclosure.is_current && 'border-emerald-500',
                            )}
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <h2 className="font-medium">
                                    {t('Version :version', {
                                        version: disclosure.version,
                                    })}
                                    {disclosure.is_current && (
                                        <span className="ms-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                            {t('Current')}
                                        </span>
                                    )}
                                </h2>
                                <div className="flex items-center gap-3 text-xs text-muted-foreground">
                                    <span>
                                        {disclosure.published_at
                                            ? t('Published :date', {
                                                  date: dateTime(
                                                      disclosure.published_at,
                                                  ),
                                              })
                                            : t('Draft')}
                                    </span>
                                    <span>
                                        {t(':count advances', {
                                            count: disclosure.advances_count,
                                        })}
                                    </span>
                                    {!disclosure.published_at && (
                                        <Form {...publish.form(disclosure.id)}>
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    size="sm"
                                                    disabled={processing}
                                                >
                                                    <Send />
                                                    {t('Publish')}
                                                </Button>
                                            )}
                                        </Form>
                                    )}
                                </div>
                            </div>
                            <div className="grid gap-4 text-sm whitespace-pre-line text-muted-foreground lg:grid-cols-2">
                                <p dir="ltr">{disclosure.body_en}</p>
                                <p dir="rtl" lang="ar">
                                    {disclosure.body_ar}
                                </p>
                            </div>
                        </section>
                    ))}
                </div>
            </div>
        </>
    );
}

Disclosures.layout = {
    breadcrumbs: [{ title: tKey('Disclosures'), href: index() }],
};
