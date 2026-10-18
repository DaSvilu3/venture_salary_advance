import { Head, Link } from '@inertiajs/react';
import { PageHeader } from '@/components/portal/page-header';
import { TermsForm } from '@/components/portal/terms-form';
import type { TermsValues } from '@/components/portal/terms-form';
import { tKey, useT } from '@/lib/i18n';
import { edit, update } from '@/routes/admin/terms';
import { show as showEmployer } from '@/routes/admin/employers';

type Props = {
    terms: TermsValues;
    overrides: (TermsValues & {
        employer_id: number;
        employer: string | null;
    })[];
};

export default function Terms({ terms, overrides }: Props) {
    const t = useT();

    return (
        <>
            <Head title={t('Program terms')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Program terms')}
                    description={t(
                        'Platform defaults for every employer without its own terms. Changes apply to new advances only.',
                    )}
                />

                <section className="rounded-xl border bg-card p-4">
                    <TermsForm
                        action={update.form()}
                        values={terms}
                        submitLabel={t('Save platform terms')}
                    />
                </section>

                <section className="space-y-2">
                    <h2 className="font-medium">
                        {t('Employers with their own terms')}
                    </h2>
                    {overrides.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {t('None. Every employer uses the platform terms.')}
                        </p>
                    ) : (
                        <ul className="divide-y rounded-xl border bg-card">
                            {overrides.map((override) => (
                                <li
                                    key={override.employer_id}
                                    className="flex items-center justify-between px-4 py-3 text-sm"
                                >
                                    <Link
                                        href={showEmployer(
                                            override.employer_id,
                                        )}
                                        className="font-medium hover:underline"
                                    >
                                        {override.employer}
                                    </Link>
                                    <span className="text-muted-foreground">
                                        {t(
                                            ':percent% up to OMR :cap · fee OMR :fee',
                                            {
                                                percent:
                                                    override.limit_ratio_percent,
                                                cap: override.max_limit,
                                                fee: override.flat_fee,
                                            },
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

Terms.layout = {
    breadcrumbs: [{ title: tKey('Program terms'), href: edit() }],
};
