<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import ProductController from '@/actions/App/Http/Controllers/Catalog/ProductController';
import FormCurrencyInput from '@/components/forms/FormCurrencyInput.vue';
import FormField from '@/components/forms/FormField.vue';
import BarcodeBuilder from '@/components/products/BarcodeBuilder.vue';
import type { BarcodeRow } from '@/components/products/BarcodeBuilder.vue';
import ChangeCostDialog from '@/components/products/ChangeCostDialog.vue';
import ChangePriceDialog from '@/components/products/ChangePriceDialog.vue';
import VariantBuilder from '@/components/products/VariantBuilder.vue';
import type { VariantRow } from '@/components/products/VariantBuilder.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { formatCurrency } from '@/lib/format';
import { priceHistory as priceHistoryRoute } from '@/routes/products';
import type {
    Brand,
    Category,
    Product,
    ProductAttribute,
    ProductType,
    Tax,
    TrackingType,
    Unit,
} from '@/types';

const props = defineProps<{
    product?: Product;
    categoryOptions: Category[];
    brandOptions: Brand[];
    unitOptions: Unit[];
    taxOptions: Tax[];
    attributeOptions: ProductAttribute[];
}>();

const productTypes: { value: ProductType; label: string }[] = [
    { value: 'physical', label: 'Físico' },
    { value: 'service', label: 'Servicio' },
    { value: 'composite', label: 'Compuesto' },
];

const trackingTypes: { value: TrackingType; label: string }[] = [
    { value: 'simple', label: 'Simple' },
    { value: 'variants', label: 'Variantes' },
    { value: 'lot', label: 'Lote' },
    { value: 'expiration', label: 'Caducidad' },
    { value: 'lot_and_expiration', label: 'Lote y caducidad' },
    { value: 'none', label: 'Sin seguimiento (servicio)' },
];

const form = useForm({
    category_id: props.product?.category_id ?? null,
    brand_id: props.product?.brand_id ?? null,
    unit_id: props.product?.unit_id ?? props.unitOptions[0]?.id ?? null,
    tax_id: props.product?.tax_id ?? null,
    name: props.product?.name ?? '',
    short_name: props.product?.short_name ?? '',
    description: props.product?.description ?? '',
    sku: props.product?.sku ?? '',
    internal_code: props.product?.internal_code ?? '',
    product_type: props.product?.product_type ?? 'physical',
    tracking_type: props.product?.tracking_type ?? 'simple',
    minimum_stock: props.product?.minimum_stock ?? '0',
    maximum_stock: props.product?.maximum_stock ?? '',
    allows_negative_stock: props.product?.allows_negative_stock ?? false,
    visible_in_pos: props.product?.visible_in_pos ?? true,
    is_favorite: props.product?.is_favorite ?? false,
    status: props.product?.status ?? 'active',
    cost: props.product?.cost ?? '0',
    sale_price: props.product?.sale_price ?? '0',
    minimum_price: props.product?.minimum_price ?? '',
    wholesale_price: props.product?.wholesale_price ?? '',
    variants: (props.product?.variants ?? []) as VariantRow[],
    barcodes: (props.product?.barcodes ?? []) as BarcodeRow[],
});

function submit() {
    if (props.product) {
        form.put(ProductController.update.url(props.product.id));
    } else {
        form.post(ProductController.store.url());
    }
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <Tabs default-value="general" class="w-full">
            <TabsList class="flex-wrap">
                <TabsTrigger value="general">Información general</TabsTrigger>
                <TabsTrigger value="classification">Clasificación</TabsTrigger>
                <TabsTrigger value="pricing">Precio y costo</TabsTrigger>
                <TabsTrigger value="inventory">Inventario</TabsTrigger>
                <TabsTrigger value="variants">Variantes</TabsTrigger>
                <TabsTrigger value="barcodes">Códigos de barras</TabsTrigger>
                <TabsTrigger value="pos">Configuración POS</TabsTrigger>
            </TabsList>

            <TabsContent value="general" class="space-y-4 pt-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        label="Nombre"
                        for="name"
                        required
                        :error="form.errors.name"
                    >
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            autofocus
                        />
                    </FormField>
                    <FormField
                        label="Nombre corto"
                        for="short_name"
                        :error="form.errors.short_name"
                    >
                        <Input id="short_name" v-model="form.short_name" />
                    </FormField>
                    <FormField
                        label="SKU"
                        for="sku"
                        required
                        :error="form.errors.sku"
                    >
                        <Input
                            id="sku"
                            v-model="form.sku"
                            required
                            class="uppercase"
                        />
                    </FormField>
                    <FormField
                        label="Código interno"
                        for="internal_code"
                        :error="form.errors.internal_code"
                    >
                        <Input
                            id="internal_code"
                            v-model="form.internal_code"
                        />
                    </FormField>
                    <FormField
                        label="Descripción"
                        for="description"
                        class="sm:col-span-2"
                        :error="form.errors.description"
                    >
                        <Textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                        />
                    </FormField>
                </div>
            </TabsContent>

            <TabsContent value="classification" class="space-y-4 pt-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        label="Categoría"
                        for="category_id"
                        :error="form.errors.category_id"
                    >
                        <Select v-model="form.category_id">
                            <SelectTrigger id="category_id" class="w-full">
                                <SelectValue placeholder="Sin categoría" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null"
                                    >Sin categoría</SelectItem
                                >
                                <SelectItem
                                    v-for="option in categoryOptions"
                                    :key="option.id"
                                    :value="option.id"
                                >
                                    {{ option.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </FormField>
                    <FormField
                        label="Marca"
                        for="brand_id"
                        :error="form.errors.brand_id"
                    >
                        <Select v-model="form.brand_id">
                            <SelectTrigger id="brand_id" class="w-full">
                                <SelectValue placeholder="Sin marca" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Sin marca</SelectItem>
                                <SelectItem
                                    v-for="option in brandOptions"
                                    :key="option.id"
                                    :value="option.id"
                                >
                                    {{ option.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </FormField>
                    <FormField
                        label="Unidad"
                        for="unit_id"
                        required
                        :error="form.errors.unit_id"
                    >
                        <Select v-model="form.unit_id">
                            <SelectTrigger id="unit_id" class="w-full">
                                <SelectValue
                                    placeholder="Selecciona una unidad"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in unitOptions"
                                    :key="option.id"
                                    :value="option.id"
                                >
                                    {{ option.name }} ({{ option.symbol }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </FormField>
                    <FormField
                        label="Impuesto"
                        for="tax_id"
                        :error="form.errors.tax_id"
                    >
                        <Select v-model="form.tax_id">
                            <SelectTrigger id="tax_id" class="w-full">
                                <SelectValue placeholder="Sin impuesto" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null"
                                    >Sin impuesto</SelectItem
                                >
                                <SelectItem
                                    v-for="option in taxOptions"
                                    :key="option.id"
                                    :value="option.id"
                                >
                                    {{ option.name }} ({{ option.rate }}%)
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </FormField>
                    <FormField
                        label="Tipo de producto"
                        for="product_type"
                        required
                        :error="form.errors.product_type"
                    >
                        <Select v-model="form.product_type">
                            <SelectTrigger id="product_type" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in productTypes"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </FormField>
                    <FormField
                        label="Seguimiento de inventario"
                        for="tracking_type"
                        required
                        :error="form.errors.tracking_type"
                    >
                        <Select v-model="form.tracking_type">
                            <SelectTrigger id="tracking_type" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in trackingTypes"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </FormField>
                </div>
            </TabsContent>

            <TabsContent value="pricing" class="space-y-4 pt-4">
                <div v-if="!product" class="grid gap-4 sm:grid-cols-2">
                    <FormCurrencyInput
                        id="cost"
                        v-model="form.cost"
                        label="Costo inicial"
                        required
                        :error="form.errors.cost"
                    />
                    <FormCurrencyInput
                        id="sale_price"
                        v-model="form.sale_price"
                        label="Precio de venta inicial"
                        required
                        :error="form.errors.sale_price"
                    />
                    <FormCurrencyInput
                        id="minimum_price"
                        v-model="form.minimum_price"
                        label="Precio mínimo"
                        tooltip="Precio más bajo al que un cajero puede vender este producto, incluso con descuento."
                        :error="form.errors.minimum_price"
                    />
                    <FormCurrencyInput
                        id="wholesale_price"
                        v-model="form.wholesale_price"
                        label="Precio de mayoreo"
                        tooltip="Precio aplicado automáticamente para ventas por mayoreo, si el módulo de listas de precios lo utiliza."
                        :error="form.errors.wholesale_price"
                    />
                </div>
                <div v-else class="space-y-4">
                    <div
                        class="flex flex-wrap items-center gap-6 rounded-lg border p-4"
                    >
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Costo actual
                            </p>
                            <p class="text-lg font-semibold">
                                {{
                                    product.cost !== undefined
                                        ? formatCurrency(product.cost)
                                        : '—'
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Precio de venta actual
                            </p>
                            <p class="text-lg font-semibold">
                                {{ formatCurrency(product.sale_price) }}
                            </p>
                        </div>
                        <div class="ml-auto flex gap-2">
                            <ChangeCostDialog
                                v-if="product.cost !== undefined"
                                :product-id="product.id"
                                :current-cost="product.cost"
                            />
                            <ChangePriceDialog
                                :product-id="product.id"
                                :current-price="product.sale_price"
                            />
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="priceHistoryRoute(product.id)"
                                    >Ver historial</Link
                                >
                            </Button>
                        </div>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Los cambios de precio y costo se registran por separado
                        con motivo, para conservar el historial completo.
                    </p>
                </div>
            </TabsContent>

            <TabsContent value="inventory" class="space-y-4 pt-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        label="Stock mínimo"
                        for="minimum_stock"
                        required
                        :error="form.errors.minimum_stock"
                    >
                        <Input
                            id="minimum_stock"
                            v-model="form.minimum_stock"
                            type="number"
                            step="0.0001"
                            min="0"
                            required
                        />
                    </FormField>
                    <FormField
                        label="Stock máximo"
                        for="maximum_stock"
                        :error="form.errors.maximum_stock"
                    >
                        <Input
                            id="maximum_stock"
                            v-model="form.maximum_stock"
                            type="number"
                            step="0.0001"
                            min="0"
                        />
                    </FormField>
                    <FormField
                        label="Permitir venta sin stock"
                        for="allows_negative_stock"
                        :error="form.errors.allows_negative_stock"
                        tooltip="Permite vender este producto aunque el inventario disponible llegue a cero o quede negativo."
                    >
                        <div class="flex h-9 items-center">
                            <Switch
                                id="allows_negative_stock"
                                v-model="form.allows_negative_stock"
                            />
                        </div>
                    </FormField>
                </div>
            </TabsContent>

            <TabsContent value="variants" class="pt-4">
                <VariantBuilder
                    v-model="form.variants"
                    :base-sku="form.sku || 'PROD'"
                    :base-cost="form.cost"
                    :base-sale-price="form.sale_price"
                    :attribute-options="attributeOptions"
                />
            </TabsContent>

            <TabsContent value="barcodes" class="pt-4">
                <BarcodeBuilder v-model="form.barcodes" />
            </TabsContent>

            <TabsContent value="pos" class="space-y-4 pt-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        label="Visible en POS"
                        for="visible_in_pos"
                        :error="form.errors.visible_in_pos"
                    >
                        <div class="flex h-9 items-center">
                            <Switch
                                id="visible_in_pos"
                                v-model="form.visible_in_pos"
                            />
                        </div>
                    </FormField>
                    <FormField
                        label="Favorito"
                        for="is_favorite"
                        :error="form.errors.is_favorite"
                    >
                        <div class="flex h-9 items-center">
                            <Switch
                                id="is_favorite"
                                v-model="form.is_favorite"
                            />
                        </div>
                    </FormField>
                    <FormField
                        label="Estado"
                        for="status"
                        required
                        :error="form.errors.status"
                    >
                        <Select v-model="form.status">
                            <SelectTrigger id="status" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Activo</SelectItem>
                                <SelectItem value="inactive"
                                    >Inactivo</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </FormField>
                </div>
            </TabsContent>
        </Tabs>

        <Button type="submit" :disabled="form.processing">
            {{ product ? 'Guardar cambios' : 'Crear producto' }}
        </Button>
    </form>
</template>
