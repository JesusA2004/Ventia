<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ListIcon, PencilIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import ProductAttributeController from '@/actions/App/Http/Controllers/Catalog/ProductAttributeController';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/products/attributes';
import type { ProductAttribute } from '@/types';

const props = defineProps<{
    attributes: ProductAttribute[];
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Atributos de variantes', href: index() }],
    },
});

function destroy(attribute: ProductAttribute) {
    router.delete(ProductAttributeController.destroy.url(attribute.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Atributos de variantes" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Atributos de variantes"
            description="Talla, color, sabor y demás atributos usados para armar variantes de producto."
        >
            <template #actions>
                <Button v-if="can('products.create')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nuevo atributo
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <EmptyState
            v-if="props.attributes.length === 0"
            :icon="ListIcon"
            title="Sin atributos"
            description="Crea atributos como Talla o Color para poder generar variantes de producto."
        />

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="attribute in props.attributes" :key="attribute.id">
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle class="text-base">{{
                        attribute.name
                    }}</CardTitle>
                    <div class="flex gap-1">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    as-child
                                    size="icon"
                                    variant="ghost"
                                    aria-label="Editar atributo"
                                >
                                    <Link
                                        :href="
                                            ProductAttributeController.edit.url(
                                                attribute.id,
                                            )
                                        "
                                    >
                                        <PencilIcon />
                                    </Link>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Editar atributo</TooltipContent>
                        </Tooltip>
                        <ConfirmationDialog
                            title="¿Eliminar atributo?"
                            :description="`No podrás eliminar «${attribute.name}» si está en uso por alguna variante.`"
                            tooltip="Eliminar atributo"
                            @confirm="destroy(attribute)"
                        >
                            <template #trigger>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    aria-label="Eliminar atributo"
                                >
                                    <Trash2Icon />
                                </Button>
                            </template>
                        </ConfirmationDialog>
                    </div>
                </CardHeader>
                <CardContent class="flex flex-wrap gap-1">
                    <Badge
                        v-for="value in attribute.values"
                        :key="value.id"
                        variant="secondary"
                    >
                        {{ value.value }}
                    </Badge>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
