import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });
}

/**
 * Safety net so a server/network failure is never silent: without this, a
 * raw 500/503 or a dropped connection just swaps the page for Laravel's
 * default error HTML (or nothing, for a network error) with no explanation
 * and no way back except the browser's own back button. 419 (expired
 * session) is left to Inertia's own default handling — it already forces a
 * full reload, which is the correct recovery — but still gets a toast
 * explaining why.
 */
export function initializeErrorToasts(): void {
    router.on('httpException', (event) => {
        const status = event.detail.response.status;

        if (status === 419) {
            toast.warning(
                'Tu sesión expiró. La página se recargará automáticamente.',
            );

            return;
        }

        toast.error('Ocurrió un error en el servidor. Intenta de nuevo.', {
            action: {
                label: 'Reintentar',
                onClick: () => router.reload(),
            },
        });

        event.preventDefault();
    });

    router.on('networkError', () => {
        toast.error(
            'No se pudo conectar con el servidor. Revisa tu conexión e intenta de nuevo.',
            { action: { label: 'Reintentar', onClick: () => router.reload() } },
        );
    });
}
