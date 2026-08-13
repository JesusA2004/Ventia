<?php

namespace Database\Seeders;

use App\Actions\Inventory\RecordInventoryMovementAction;
use App\Enums\BarcodeType;
use App\Enums\InventoryMovementType;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TaxType;
use App\Enums\TrackingType;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Company;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductBarcode;
use App\Models\ProductLot;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Demo catalog + inventory for the "Ventia Demo" company: enough variety
 * (simple, bulk, variants, lot, expiration) to exercise every product
 * type without generating hundreds of throwaway rows.
 */
class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('tax_id', 'VEN010101ABC')->firstOrFail();
        $branch = Branch::where('company_id', $company->id)->where('code', 'SUC-001')->firstOrFail();
        $warehouse = Warehouse::where('branch_id', $branch->id)->where('code', 'ALM-001')->firstOrFail();
        $admin = User::where('email', 'admin@ventia-demo.test')->firstOrFail();

        $piece = Unit::whereNull('company_id')->where('symbol', 'PZA')->firstOrFail();
        $kilogram = Unit::whereNull('company_id')->where('symbol', 'KG')->firstOrFail();

        $iva = Tax::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'IVA16'],
            ['name' => 'IVA 16%', 'rate' => 16, 'type' => TaxType::Percentage, 'included_in_price' => true, 'status' => Status::Active],
        );
        Tax::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'EXENTO'],
            ['name' => 'Exento', 'rate' => 0, 'type' => TaxType::Exempt, 'included_in_price' => true, 'status' => Status::Active],
        );

        $abarrotes = Category::firstOrCreate(
            ['company_id' => $company->id, 'parent_id' => null, 'name' => 'Abarrotes'],
            ['slug' => 'abarrotes', 'sort_order' => 1, 'status' => Status::Active],
        );
        $bebidas = Category::firstOrCreate(
            ['company_id' => $company->id, 'parent_id' => $abarrotes->id, 'name' => 'Bebidas'],
            ['slug' => 'bebidas', 'sort_order' => 1, 'status' => Status::Active],
        );
        $farmacia = Category::firstOrCreate(
            ['company_id' => $company->id, 'parent_id' => null, 'name' => 'Farmacia'],
            ['slug' => 'farmacia', 'sort_order' => 2, 'status' => Status::Active],
        );
        $pasteleria = Category::firstOrCreate(
            ['company_id' => $company->id, 'parent_id' => null, 'name' => 'Pastelería'],
            ['slug' => 'pasteleria', 'sort_order' => 3, 'status' => Status::Active],
        );

        $genericBrand = Brand::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Genérica'],
            ['slug' => 'generica', 'status' => Status::Active],
        );

        $general = PriceList::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'GENERAL'],
            ['name' => 'General', 'currency' => $company->currency, 'priority' => 100, 'is_default' => true, 'status' => Status::Active],
        );
        $wholesale = PriceList::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'MAYOREO'],
            ['name' => 'Mayoreo', 'currency' => $company->currency, 'priority' => 50, 'status' => Status::Active],
        );

        $recorder = app(RecordInventoryMovementAction::class);

        // 1) Simple product with a primary barcode and a wholesale price override.
        $soda = Product::firstOrCreate(
            ['company_id' => $company->id, 'sku' => 'REF-COLA-600'],
            [
                'category_id' => $bebidas->id,
                'brand_id' => $genericBrand->id,
                'unit_id' => $piece->id,
                'tax_id' => $iva->id,
                'name' => 'Refresco de cola 600ml',
                'slug' => 'refresco-de-cola-600ml',
                'sku' => 'REF-COLA-600',
                'product_type' => ProductType::Physical,
                'tracking_type' => TrackingType::Simple,
                'cost' => '12.0000',
                'sale_price' => '18.0000',
                'minimum_stock' => '24',
                'allows_negative_stock' => false,
                'visible_in_pos' => true,
                'status' => Status::Active,
            ],
        );
        ProductBarcode::firstOrCreate(
            ['company_id' => $company->id, 'barcode' => '7501234567890'],
            ['product_id' => $soda->id, 'type' => BarcodeType::Ean13, 'is_primary' => true, 'quantity_multiplier' => 1],
        );
        ProductPrice::firstOrCreate(
            ['company_id' => $company->id, 'product_id' => $soda->id, 'price_list_id' => $wholesale->id, 'branch_id' => null, 'min_quantity' => '12'],
            ['price' => '15.5000', 'status' => 'active'],
        );

        // 2) Bulk product sold by weight.
        $rice = Product::firstOrCreate(
            ['company_id' => $company->id, 'sku' => 'ARROZ-GRANEL'],
            [
                'category_id' => $abarrotes->id,
                'unit_id' => $kilogram->id,
                'tax_id' => $iva->id,
                'name' => 'Arroz a granel',
                'slug' => 'arroz-a-granel',
                'sku' => 'ARROZ-GRANEL',
                'product_type' => ProductType::Physical,
                'tracking_type' => TrackingType::Simple,
                'cost' => '18.0000',
                'sale_price' => '25.0000',
                'minimum_stock' => '10',
                'visible_in_pos' => true,
                'status' => Status::Active,
            ],
        );

        // 3) Product with variants (Talla x Color).
        $sizeAttribute = ProductAttribute::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Talla'],
            ['status' => Status::Active],
        );
        $colorAttribute = ProductAttribute::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Color'],
            ['status' => Status::Active],
        );
        $sizeMedium = ProductAttributeValue::firstOrCreate(['product_attribute_id' => $sizeAttribute->id, 'value' => 'Mediana'], ['sort_order' => 1]);
        $sizeLarge = ProductAttributeValue::firstOrCreate(['product_attribute_id' => $sizeAttribute->id, 'value' => 'Grande'], ['sort_order' => 2]);
        $colorBlack = ProductAttributeValue::firstOrCreate(['product_attribute_id' => $colorAttribute->id, 'value' => 'Negro'], ['sort_order' => 1]);
        $colorWhite = ProductAttributeValue::firstOrCreate(['product_attribute_id' => $colorAttribute->id, 'value' => 'Blanco'], ['sort_order' => 2]);

        $shirt = Product::firstOrCreate(
            ['company_id' => $company->id, 'sku' => 'PLAYERA-BASICA'],
            [
                'unit_id' => $piece->id,
                'tax_id' => $iva->id,
                'name' => 'Playera básica',
                'slug' => 'playera-basica',
                'sku' => 'PLAYERA-BASICA',
                'product_type' => ProductType::Physical,
                'tracking_type' => TrackingType::Variants,
                'cost' => '60.0000',
                'sale_price' => '99.0000',
                'minimum_stock' => '5',
                'visible_in_pos' => true,
                'status' => Status::Active,
            ],
        );

        $variantCombinations = [
            ['sku' => 'PLAYERA-M-NEGRO', 'values' => [$sizeMedium->id, $colorBlack->id]],
            ['sku' => 'PLAYERA-G-BLANCO', 'values' => [$sizeLarge->id, $colorWhite->id]],
        ];

        foreach ($variantCombinations as $combination) {
            $variant = ProductVariant::firstOrCreate(
                ['company_id' => $company->id, 'sku' => $combination['sku']],
                ['product_id' => $shirt->id, 'cost' => $shirt->cost, 'sale_price' => $shirt->sale_price, 'status' => Status::Active],
            );
            $variant->attributeValues()->sync($combination['values']);

            $recorder->execute([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'product_id' => $shirt->id,
                'product_variant_id' => $variant->id,
                'movement_type' => InventoryMovementType::Initial,
                'quantity' => '80',
                'unit_cost' => (string) $variant->cost,
                'reason' => 'Inventario inicial (seeder demo)',
                'performed_by' => $admin->id,
            ]);
        }

        // 4) Lot-tracked product (no expiration).
        $flour = Product::firstOrCreate(
            ['company_id' => $company->id, 'sku' => 'HARINA-LOTE'],
            [
                'category_id' => $pasteleria->id,
                'unit_id' => $kilogram->id,
                'tax_id' => $iva->id,
                'name' => 'Harina de trigo (por lote)',
                'slug' => 'harina-de-trigo-por-lote',
                'sku' => 'HARINA-LOTE',
                'product_type' => ProductType::Physical,
                'tracking_type' => TrackingType::Lot,
                'cost' => '15.0000',
                'sale_price' => '22.0000',
                'minimum_stock' => '15',
                'visible_in_pos' => true,
                'status' => Status::Active,
            ],
        );
        $flourLot = ProductLot::firstOrCreate(
            ['company_id' => $company->id, 'product_id' => $flour->id, 'lot_number' => 'LOTE-2026-001'],
            ['received_at' => now()->subDays(5), 'cost' => $flour->cost, 'status' => Status::Active],
        );
        $recorder->execute([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $flour->id,
            'product_lot_id' => $flourLot->id,
            'movement_type' => InventoryMovementType::Initial,
            'quantity' => '250',
            'unit_cost' => (string) $flour->cost,
            'reason' => 'Inventario inicial (seeder demo)',
            'performed_by' => $admin->id,
        ]);

        // 5) Product with lot + expiration date (pharma-style).
        $medicine = Product::firstOrCreate(
            ['company_id' => $company->id, 'sku' => 'PARACETAMOL-500'],
            [
                'category_id' => $farmacia->id,
                'unit_id' => $piece->id,
                'tax_id' => $iva->id,
                'name' => 'Paracetamol 500mg (caja 10 tabletas)',
                'slug' => 'paracetamol-500mg',
                'sku' => 'PARACETAMOL-500',
                'product_type' => ProductType::Physical,
                'tracking_type' => TrackingType::LotAndExpiration,
                'cost' => '28.0000',
                'sale_price' => '45.0000',
                'minimum_stock' => '10',
                'visible_in_pos' => true,
                'status' => Status::Active,
            ],
        );
        $medicineLot = ProductLot::firstOrCreate(
            ['company_id' => $company->id, 'product_id' => $medicine->id, 'lot_number' => 'LOTE-PARA-2026'],
            [
                'manufacture_date' => now()->subMonths(6),
                'expiration_date' => now()->addDays(20),
                'received_at' => now()->subMonths(5),
                'cost' => $medicine->cost,
                'status' => Status::Active,
            ],
        );
        ProductBarcode::firstOrCreate(
            ['company_id' => $company->id, 'barcode' => '7501234500019'],
            ['product_id' => $medicine->id, 'type' => BarcodeType::Ean13, 'is_primary' => true, 'quantity_multiplier' => 1],
        );
        $recorder->execute([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $medicine->id,
            'product_lot_id' => $medicineLot->id,
            'movement_type' => InventoryMovementType::Initial,
            'quantity' => '120',
            'unit_cost' => (string) $medicine->cost,
            'reason' => 'Inventario inicial (seeder demo, próximo a caducar)',
            'performed_by' => $admin->id,
        ]);

        // Initial stock for the simple/bulk products too.
        $recorder->execute([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $soda->id,
            'movement_type' => InventoryMovementType::Initial,
            'quantity' => '400',
            'unit_cost' => (string) $soda->cost,
            'reason' => 'Inventario inicial (seeder demo)',
            'performed_by' => $admin->id,
        ]);
        $recorder->execute([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $rice->id,
            'movement_type' => InventoryMovementType::Initial,
            'quantity' => '300.500',
            'unit_cost' => (string) $rice->cost,
            'reason' => 'Inventario inicial (seeder demo)',
            'performed_by' => $admin->id,
        ]);

        // Mark a few best-sellers as POS favorites for the initial grid.
        Product::query()->whereIn('id', [$soda->id, $rice->id, $shirt->id])->update(['is_favorite' => true]);
    }
}
