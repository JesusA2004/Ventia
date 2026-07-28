<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2Icon, CircleIcon } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { guide } from '@/routes/help';

type Step = {
    key: string;
    title: string;
    description: string;
    completed: boolean;
    href: string;
    can: boolean;
};

const props = defineProps<{
    steps: Step[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Primeros pasos', href: '#' }],
    },
});

const completedCount = props.steps.filter((s) => s.completed).length;
</script>

<template>
    <Head title="Primeros pasos" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Primeros pasos"
            :description="`Completa estos ${steps.length} pasos para dejar tu negocio operando. Llevas ${completedCount} de ${steps.length}.`"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="guide()">Ver guía de roles y permisos</Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-3">
            <Card
                v-for="(step, index) in steps"
                :key="step.key"
                :class="step.completed ? 'border-emerald-500/30' : ''"
            >
                <CardHeader class="flex-row items-center gap-4 space-y-0">
                    <CheckCircle2Icon
                        v-if="step.completed"
                        class="size-6 shrink-0 text-emerald-600 dark:text-emerald-400"
                    />
                    <CircleIcon
                        v-else
                        class="size-6 shrink-0 text-muted-foreground"
                    />
                    <div class="flex-1">
                        <CardTitle class="text-base">
                            {{ index + 1 }}. {{ step.title }}
                        </CardTitle>
                        <p class="text-sm text-muted-foreground">
                            {{ step.description }}
                        </p>
                    </div>
                    <Badge v-if="step.completed" variant="outline"
                        >Completado</Badge
                    >
                    <Button
                        v-else-if="step.can"
                        as-child
                        size="sm"
                        variant="outline"
                    >
                        <Link :href="step.href">Ir</Link>
                    </Button>
                </CardHeader>
                <CardContent
                    v-if="!step.completed && !step.can"
                    class="pt-0 text-xs text-muted-foreground"
                >
                    No tienes permiso para completar este paso; pídeselo a un
                    administrador.
                </CardContent>
            </Card>
        </div>
    </div>
</template>
