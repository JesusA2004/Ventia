<script setup lang="ts">
import { CircleHelpIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
import type { BarcodeType } from '@/types';

export type BarcodeRow = {
    id?: number;
    barcode: string;
    type: BarcodeType;
    is_primary: boolean;
    quantity_multiplier: string;
};

const barcodes = defineModel<BarcodeRow[]>({ default: () => [] });

const barcodeTypes: BarcodeType[] = [
    'EAN13',
    'EAN8',
    'UPC',
    'CODE128',
    'QR',
    'internal',
    'other',
];

function addBarcode() {
    barcodes.value = [
        ...barcodes.value,
        {
            barcode: '',
            type: 'other',
            is_primary: barcodes.value.length === 0,
            quantity_multiplier: '1',
        },
    ];
}

function removeBarcode(index: number) {
    barcodes.value = barcodes.value.filter((_, i) => i !== index);
}

function makePrimary(index: number) {
    barcodes.value = barcodes.value.map((barcode, i) => ({
        ...barcode,
        is_primary: i === index,
    }));
}
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="barcodes.length > 0"
            class="overflow-x-auto rounded-lg border"
        >
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Código</TableHead>
                        <TableHead>Tipo</TableHead>
                        <TableHead>
                            <span class="inline-flex items-center gap-1.5">
                                Multiplicador (caja/paquete)
                                <Tooltip>
                                    <TooltipTrigger
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground"
                                        aria-label="Ayuda: Multiplicador"
                                    >
                                        <CircleHelpIcon class="size-3.5" />
                                    </TooltipTrigger>
                                    <TooltipContent
                                        >Indica cuántas unidades base representa
                                        este código de barras cuando
                                        corresponde a una caja o
                                        paquete.</TooltipContent
                                    >
                                </Tooltip>
                            </span>
                        </TableHead>
                        <TableHead>Principal</TableHead>
                        <TableHead class="text-right">Quitar</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="(barcode, index) in barcodes" :key="index">
                        <TableCell>
                            <Input
                                v-model="barcode.barcode"
                                placeholder="7501234567890"
                            />
                        </TableCell>
                        <TableCell>
                            <Select v-model="barcode.type">
                                <SelectTrigger class="w-32">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="type in barcodeTypes"
                                        :key="type"
                                        :value="type"
                                    >
                                        {{ type }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </TableCell>
                        <TableCell>
                            <Input
                                v-model="barcode.quantity_multiplier"
                                type="number"
                                step="0.0001"
                                min="0.0001"
                                class="w-24"
                            />
                        </TableCell>
                        <TableCell>
                            <Button
                                type="button"
                                size="sm"
                                :variant="
                                    barcode.is_primary ? 'default' : 'outline'
                                "
                                @click="makePrimary(index)"
                            >
                                {{
                                    barcode.is_primary
                                        ? 'Principal'
                                        : 'Marcar principal'
                                }}
                            </Button>
                        </TableCell>
                        <TableCell class="text-right">
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                @click="removeBarcode(index)"
                            >
                                <Trash2Icon />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
        <p v-else class="text-sm text-muted-foreground">
            Sin códigos de barras registrados.
        </p>

        <Button type="button" variant="outline" size="sm" @click="addBarcode">
            <PlusIcon />
            Agregar código de barras
        </Button>
    </div>
</template>
