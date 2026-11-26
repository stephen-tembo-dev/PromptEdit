import '@inertiajs/core';
import type { ToastPayload } from '@/composables/useToast';

export interface User {
    id: number;
    name: string;
    email: string;
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: {
                user: User | null;
                /** What the navigation may offer; the routes enforce it. */
                can: {
                    viewDashboard: boolean;
                    generateImages: boolean;
                    viewHistory: boolean;
                    manageCredits: boolean;
                    viewReports: boolean;
                    viewAdminReport: boolean;
                    manageUsers: boolean;
                    manageRoles: boolean;
                };
            };
            /** The signed-in user's spendable credit, formatted (e.g. "$4.5327"); null when signed out. */
            balance: string | null;
            notify: ToastPayload | null;
        };
        flashDataType: {
            notify?: ToastPayload;
        };
    }
}
