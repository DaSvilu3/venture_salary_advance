import { Form, Head, useForm } from '@inertiajs/react';
import { Download, FileUp, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useRef } from 'react';
import InputError from '@/components/input-error';
import { Field } from '@/components/portal/field';
import { PageHeader } from '@/components/portal/page-header';
import { StatusPill } from '@/components/portal/status-pill';
import { EmptyRow, TD, TH, THead, TR, Table } from '@/components/portal/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { CompanyDocument } from '@/lib/documents';
import { documentStatusMeta, documentTypeMeta } from '@/lib/documents';
import { useDates } from '@/lib/format';
import { tKey, useT } from '@/lib/i18n';
import { destroy, download, index, store } from '@/routes/employer/documents';

type Props = {
    documents: CompanyDocument[];
    types: string[];
};

export default function Documents({ documents, types }: Props) {
    const t = useT();
    const { date } = useDates();
    const fileInput = useRef<HTMLInputElement>(null);
    const form = useForm<{ type: string; file: File | null }>({
        type: types[0] ?? 'commercial_registration',
        file: null,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(store.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset('file');
                if (fileInput.current) {
                    fileInput.current.value = '';
                }
            },
        });
    };

    return (
        <>
            <Head title={t('Company documents')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Company documents')}
                    description={t(
                        'Upload your company papers. Our team reviews each one before your company can use salary advances.',
                    )}
                />

                <section className="rounded-xl border bg-card p-4">
                    <h2 className="mb-4 font-medium">{t('Upload document')}</h2>
                    <form
                        onSubmit={submit}
                        className="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                    >
                        <Field
                            label={t('Document type')}
                            htmlFor="document_type"
                            error={form.errors.type}
                        >
                            {/* Native select: its value always submits, as on the simulation page. */}
                            <select
                                id="document_type"
                                name="type"
                                value={form.data.type}
                                onChange={(event) =>
                                    form.setData('type', event.target.value)
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                            >
                                {types.map((type) => (
                                    <option key={type} value={type}>
                                        {t(
                                            documentTypeMeta[type]?.label ??
                                                type,
                                        )}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field
                            label={t('File')}
                            htmlFor="document_file"
                            error={form.errors.file}
                            hint={t('PDF, JPG or PNG up to 5 MB.')}
                        >
                            <Input
                                ref={fileInput}
                                id="document_file"
                                type="file"
                                name="file"
                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                required
                                onChange={(event) =>
                                    form.setData(
                                        'file',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                        </Field>
                        <Button
                            type="submit"
                            disabled={
                                form.processing || form.data.file === null
                            }
                        >
                            {form.processing ? <Spinner /> : <FileUp />}
                            {t('Upload')}
                        </Button>
                    </form>
                    <InputError message={form.errors.type} className="mt-2" />
                </section>

                <Table>
                    <THead>
                        <tr>
                            <TH>{t('Type')}</TH>
                            <TH>{t('Document')}</TH>
                            <TH>{t('Status')}</TH>
                            <TH>{t('Uploaded')}</TH>
                            <TH>
                                <span className="sr-only">{t('Actions')}</span>
                            </TH>
                        </tr>
                    </THead>
                    <tbody>
                        {documents.length === 0 && (
                            <EmptyRow colSpan={5}>
                                {t('Nothing uploaded yet.')}
                            </EmptyRow>
                        )}
                        {documents.map((item) => (
                            <TR key={item.id}>
                                <TD>
                                    {t(
                                        documentTypeMeta[item.type]?.label ??
                                            item.type,
                                    )}
                                </TD>
                                <TD>
                                    <span dir="ltr" className="break-all">
                                        {item.original_name}
                                    </span>
                                    {item.status === 'rejected' &&
                                        item.review_note && (
                                            <p className="mt-1 text-xs text-red-700 dark:text-red-300">
                                                {t('Reason:')}{' '}
                                                {item.review_note}
                                            </p>
                                        )}
                                </TD>
                                <TD>
                                    <StatusPill
                                        meta={documentStatusMeta[item.status]}
                                    />
                                </TD>
                                <TD className="whitespace-nowrap text-muted-foreground">
                                    {item.uploaded_at
                                        ? date(item.uploaded_at)
                                        : '—'}
                                </TD>
                                <TD>
                                    <div className="flex items-center justify-end gap-1">
                                        <Button
                                            asChild
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <a href={download.url(item.id)}>
                                                <Download />
                                                {t('Download')}
                                            </a>
                                        </Button>
                                        <Form
                                            {...destroy.form(item.id)}
                                            options={{ preserveScroll: true }}
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled={processing}
                                                >
                                                    <Trash2 />
                                                    {t('Delete')}
                                                </Button>
                                            )}
                                        </Form>
                                    </div>
                                </TD>
                            </TR>
                        ))}
                    </tbody>
                </Table>
            </div>
        </>
    );
}

Documents.layout = {
    breadcrumbs: [{ title: tKey('Company documents'), href: index() }],
};
