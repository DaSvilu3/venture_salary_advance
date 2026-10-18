import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useT } from '@/lib/i18n';

export function PartnerSelect({
    name,
    options,
    defaultValue,
}: {
    name: string;
    options: { id: number; name: string }[];
    defaultValue?: number | null;
}) {
    const t = useT();

    return (
        <Select
            name={name}
            defaultValue={defaultValue ? String(defaultValue) : undefined}
        >
            <SelectTrigger className="w-full">
                <SelectValue placeholder={t('Choose…')} />
            </SelectTrigger>
            <SelectContent>
                {options.map((option) => (
                    <SelectItem key={option.id} value={String(option.id)}>
                        {option.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
