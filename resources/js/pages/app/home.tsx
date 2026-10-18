import { Head, usePage } from '@inertiajs/react';
import { useT } from '@/lib/i18n';

export default function Home() {
    const { auth } = usePage().props;
    const t = useT();

    return (
        <>
            <Head title={t('Home')} />
            <h1 className="text-lg font-semibold">
                {t('Hello, :name', { name: auth.user.name })}
            </h1>
        </>
    );
}
