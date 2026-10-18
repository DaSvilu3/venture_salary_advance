import type { Locale } from '@/lib/i18n';
import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            locale: Locale;
            direction: 'ltr' | 'rtl';
            translations: Record<string, string>;
            clock: {
                today: string;
                isSimulated: boolean;
            };
            [key: string]: unknown;
        };
    }
}
