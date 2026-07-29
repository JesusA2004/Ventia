<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';

const props = defineProps<{ status: number }>();

const messages: Record<number, { title: string; description: string }> = {
    403: {
        title: 'Acceso denegado',
        description: 'No tienes permiso para ver esta página.',
    },
    404: {
        title: 'Página no encontrada',
        description: 'La página que buscas no existe o fue movida.',
    },
    419: {
        title: 'Sesión expirada',
        description: 'Tu sesión expiró por inactividad. Vuelve a intentarlo.',
    },
    422: {
        title: 'Solicitud inválida',
        description: 'No se pudo procesar la información enviada.',
    },
    500: {
        title: 'Error del servidor',
        description: 'Ocurrió un error inesperado. Ya fue registrado.',
    },
    503: {
        title: 'Servicio no disponible',
        description:
            'Ventia está en mantenimiento. Vuelve a intentarlo en unos minutos.',
    },
};

const content = messages[props.status] ?? {
    title: `Error ${props.status}`,
    description: 'Ocurrió un problema al procesar tu solicitud.',
};

function goBack() {
    window.history.back();
}
</script>

<template>
    <Head :title="content.title" />

    <div
        class="flex min-h-dvh flex-col items-center justify-center gap-6 bg-background px-6 py-10"
    >
        <Link :href="'/'" class="flex items-center gap-2">
            <span
                class="flex size-9 items-center justify-center rounded-lg bg-indigo-600 text-white"
            >
                <AppLogoIcon class="size-5" />
            </span>
            <span class="text-lg font-semibold tracking-tight">Ventia</span>
        </Link>

        <div
            class="flex w-full max-w-sm flex-col items-center gap-2 text-center"
        >
            <p class="text-sm font-medium text-muted-foreground">
                Error {{ status }}
            </p>
            <h1 class="text-xl font-semibold tracking-tight">
                {{ content.title }}
            </h1>
            <p class="text-sm text-muted-foreground">
                {{ content.description }}
            </p>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-2">
            <Button variant="outline" @click="goBack">Volver</Button>
            <Button as-child>
                <Link :href="dashboard()">Ir al dashboard</Link>
            </Button>
            <Button variant="ghost" as-child>
                <Link :href="login()">Iniciar sesión</Link>
            </Button>
        </div>
    </div>
</template>
