import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/portal/page-header';
import { tKey, useT } from '@/lib/i18n';
import { dashboard } from '@/routes/employer';

export default function Dashboard() {
    const t = useT();

    return (
        <>
            <Head title={t('Employer dashboard')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Employer dashboard')}
                    description={t(
                        "Your staff's enrolment and usage at a glance.",
                    )}
                />
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: tKey('Dashboard'), href: dashboard() }],
};
