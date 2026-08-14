<script setup lang="ts">
import { CircleHelpIcon, Trash2Icon } from '@lucide/vue';
import { computed, reactive } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import type { ProductAttribute } from '@/types';

export type VariantRow = {
    id?: number;
    sku: string;
    internal_code?: string | null;
    cost?: string;
    sale_price?: string;
    minimum_price?: string | null;
    status: string;
    attribute_value_ids: number[];
};

const props = defineProps<{
    baseSku: string;
    baseCost: string;
    baseSalePrice: string;
    attributeOptions: ProductAttribute[];
}>();

const variants = defineModel<VariantRow[]>({ default: () => [] });

const selectedValues = reactive<Record<number, number[]>>({});

function toggleValue(attributeId: number, valueId: number) {
    const current = selectedValues[attributeId] ?? [];
    selectedValues[attributeId] = current.includes(valueId)
        ? current.filter((id) => id !== valueId)
        : [...current, valueId];
}

const valueLabel = (id: number): string => {
    for (const attribute of props.attributeOptions) {
        const value = attribute.values.find((v) => v.id === id);

        if (value) {
            return value.value;
        }
    }

    return `#${id}`;
};

function combinationLabel(ids: number[]): string {
    return ids.map(valueLabel).join(' / ');
}

function sameSet(a: number[], b: number[]): boolean {
    return (
        a.length === b.length &&
        [...a].sort().join(',') === [...b].sort().join(',')
    );
}

function generateCombinations() {
    const groups = Object.values(selectedValues).filter(
        (group) => group.length > 0,
    );

    if (groups.length === 0) {
        return;
    }

    let combos: number[][] = [[]];

    for (const group of groups) {
        const next: number[][] = [];

        for (const combo of combos) {
            for (const valueId of group) {
                next.push([...combo, valueId]);
            }
        }

        combos = next;
    }

    const created = combos.filter(
        (combo) =>
            !variants.value.some((v) => sameSet(v.attribute_value_ids, combo)),
    );

    variants.value = [
        ...variants.value,
        ...created.map((combo) => ({
            sku: `${props.baseSku}-${combo.map(valueLabel).join('-')}`
                .toUpperCase()
                .replace(/\s+/g, ''),
            cost: props.baseCost,
            sale_price: props.baseSalePrice,
            minimum_price: '',
            status: 'active',
            attribute_value_ids: combo,
        })),
    ];
}

function removeVariant(index: number) {
    variants.value = variants.value.filter((_, i) => i !== index);
}

const hasAttributes = computed(() => props.attributeOptions.length > 0);
</script>

<template>
    <div class="space-y-6">
        <div
            v-if="!hasAttributes"
            class="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
        >
            No hay atributos configurados todavía. Crea atributos (Talla,
            Color...) en
            <span class="font-medium">Productos → Atributos</span> para poder
            generar variantes.
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="attribute in attributeOptions"
                :key="attribute.id"
                class="space-y-2"
            >
                <Label>{{ attribute.name }}</Label>
                <div class="flex flex-wrap gap-3">
                    <label
                        v-for="value in attribute.values"
                        :key="value.id"
                        class="flex items-center gap-1.5 text-sm"
                    >
                        <Checkbox
                            :model-value="
                                (selectedValues[attribute.id] ?? []).includes(
                                    value.id,
                                )
                            "
                            @update:model-value="
                                toggleValue(attribute.id, value.id)
                            "
                        />
                        {{ value.value }}
                    </label>
                </div>
            </div>

            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="generateCombinations"
            >
                Generar combinaciones seleccionadas
            </Button>
        </div>

        <div
            v-if="variants.length > 0"
            class="overflow-x-auto rounded-lg border"
        >
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>
                            <span class="inline-flex items-center gap-1.5">
                                Combinación
                                <Tooltip>
                                    <TooltipTrigger
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground"
                                        aria-label="Ayuda: Combinación"
                                    >
                                        <CircleHelpIcon class="size-3.5" />
                                    </TooltipTrigger>
                                    <TooltipContent
                                        >Valores de atributo que forman esta
                                        variante específica (por ejemplo,
                                        Talla: Chica / Color: Rojo).</TooltipContent
                                    >
                                </Tooltip>
                            </span>
                        </TableHead>
                        <TableHead>
                            <span class="inline-flex items-center gap-1.5">
                                SKU
                                <Tooltip>
                                    <TooltipTrigger
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground"
                                        aria-label="Ayuda: SKU"
                                    >
                                        <CircleHelpIcon class="size-3.5" />
                                    </TooltipTrigger>
                                    <TooltipContent
                                        >Código único de esta variante,
                                        generado automáticamente y editable.</TooltipContent
                                    >
                                </Tooltip>
                            </span>
                        </TableHead>
                        <TableHead>
                            <span class="inline-flex items-center gap-1.5">
                                Costo
                                <Tooltip>
                                    <TooltipTrigger
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground"
                                        aria-label="Ayuda: Costo"
                                    >
                                        <CircleHelpIcon class="size-3.5" />
                                    </TooltipTrigger>
                                    <TooltipContent
                                        >Costo inicial de esta variante.
                                        Se precarga con el costo base del
                                        producto y puede editarse por
                                        variante.</TooltipContent
                                    >
                                </Tooltip>
                            </span>
                        </TableHead>
                        <TableHead>
                            <span class="inline-flex items-center gap-1.5">
                                Precio
                                <Tooltip>
                                    <TooltipTrigger
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground"
                                        aria-label="Ayuda: Precio"
                                    >
                                        <CircleHelpIcon class="size-3.5" />
                                    </TooltipTrigger>
                                    <TooltipContent
                                        >Precio de venta inicial de esta
                                        variante. Se precarga con el precio
                                        base del producto y puede editarse
                                        por variante.</TooltipContent
                                    >
                                </Tooltip>
                            </span>
                        </TableHead>
                        <TableHead>
                            <span class="inline-flex items-center gap-1.5">
                                Estado
                                <Tooltip>
                                    <TooltipTrigger
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground"
                                        aria-label="Ayuda: Estado"
                                    >
                                        <CircleHelpIcon class="size-3.5" />
                                    </TooltipTrigger>
                                    <TooltipContent
                                        >Una variante inactiva deja de estar
                                        disponible para venderse.</TooltipContent
                                    >
                                </Tooltip>
                            </span>
                        </TableHead>
                        <TableHead class="text-right">Quitar</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="(variant, index) in variants" :key="index">
                        <TableCell>
                            <Badge variant="outline">{{
                                combinationLabel(variant.attribute_value_ids)
                            }}</Badge>
                        </TableCell>
                        <TableCell>
                            <Input v-model="variant.sku" class="w-40" />
                        </TableCell>
                        <TableCell>
                            <Input
                                v-model="variant.cost"
                                type="number"
                                inputmode="decimal"
                                step="1"
                                min="0"
                                class="w-28"
                            />
                        </TableCell>
                        <TableCell>
                            <Input
                                v-model="variant.sale_price"
                                type="number"
                                inputmode="decimal"
                                step="1"
                                min="0"
                                class="w-28"
                            />
                        </TableCell>
                        <TableCell>
                            <Select v-model="variant.status">
                                <SelectTrigger class="w-28">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active"
                                        >Activo</SelectItem
                                    >
                                    <SelectItem value="inactive"
                                        >Inactivo</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </TableCell>
                        <TableCell class="text-right">
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                @click="removeVariant(index)"
                            >
                                <Trash2Icon />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
        <p v-else class="text-sm text-muted-foreground">
            Aún no se han generado variantes.
        </p>
    </div>
</template>
