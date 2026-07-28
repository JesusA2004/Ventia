<?php

namespace Database\Seeders;

use App\Actions\Inventory\RecordInventoryMovementAction;
use App\Enums\BarcodeType;
use App\Enums\InventoryMovementType;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TaxType;
use App\Enums\TrackingType;
use App\Enums\WarehouseType;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * A second branch (with its own warehouse/register/cashier) plus one user
 * per role and a wider product catalog, so the demo company has enough
 * breadth to exercise multi-branch reports, transfers, and every
 * permission level — not just the single-branch/two-role setup from
 * DemoDataSeeder.
 */
class BranchExpansionSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('tax_id', 'VEN010101ABC')->firstOrFail();

        $branchNorth = Branch::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'SUC-002'],
            [
                'name' => 'Sucursal Norte',
                'address' => 'Av. Insurgentes Norte 456, CDMX',
                'phone' => '5555555556',
                'status' => Status::Active,
            ],
        );

        $warehouseNorth = Warehouse::firstOrCreate(
            ['branch_id' => $branchNorth->id, 'code' => 'ALM-002'],
            [
                'company_id' => $company->id,
                'name' => 'Almacén Norte',
                'type' => WarehouseType::General,
                'allows_sale' => true,
                'status' => Status::Active,
            ],
        );

        CashRegister::firstOrCreate(
            ['branch_id' => $branchNorth->id, 'code' => 'CAJA-002'],
            [
                'company_id' => $company->id,
                'warehouse_id' => $warehouseNorth->id,
                'name' => 'Caja 2',
                'has_cash_drawer' => true,
                'status' => Status::Active,
            ],
        );

        $branchCentro = Branch::where('company_id', $company->id)->where('code', 'SUC-001')->firstOrFail();

        $this->createUser($company, 'gerente@ventia-demo.test', 'Gerente Demo', 'Gerente', [$branchCentro->id, $branchNorth->id]);
        $this->createUser($company, 'encargado@ventia-demo.test', 'Encargado Sucursal Norte', 'Encargado de sucursal', [$branchNorth->id]);
        $this->createUser($company, 'supervisor@ventia-demo.test', 'Supervisor Demo', 'Supervisor', [$branchCentro->id, $branchNorth->id]);
        $this->createUser($company, 'almacenista@ventia-demo.test', 'Almacenista Demo', 'Almacenista', [$branchCentro->id, $branchNorth->id]);
        $this->createUser($company, 'contabilidad@ventia-demo.test', 'Contabilidad Demo', 'Contabilidad', [$branchCentro->id, $branchNorth->id]);
        $this->createUser($company, 'consulta@ventia-demo.test', 'Consulta Demo', 'Consulta', [$branchCentro->id, $branchNorth->id]);
        $this->createUser($company, 'cajero2@ventia-demo.test', 'Cajero Norte', 'Cajero', [$branchNorth->id]);

        $this->seedCatalog($company, $branchCentro, $branchNorth);
    }

    /** @param  list<int>  $branchIds */
    private function createUser(Company $company, string $email, string $name, string $role, array $branchIds): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'company_id' => $company->id,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
        $user->syncRoles([$role]);
        $user->branches()->syncWithoutDetaching($branchIds);
    }

    private function seedCatalog(Company $company, Branch $branchCentro, Branch $branchNorth): void
    {
        if (Product::withoutGlobalScopes()->where('company_id', $company->id)->count() >= 12) {
            return;
        }

        $warehouseCentro = Warehouse::where('branch_id', $branchCentro->id)->where('code', 'ALM-001')->firstOrFail();
        $warehouseNorth = Warehouse::where('branch_id', $branchNorth->id)->where('code', 'ALM-002')->firstOrFail();
        $admin = User::where('email', 'admin@ventia-demo.test')->firstOrFail();

        $piece = Unit::whereNull('company_id')->where('symbol', 'PZA')->firstOrFail();
        $liter = Unit::whereNull('company_id')->where('symbol', 'L')->firstOrFail();
        $iva = Tax::where('company_id', $company->id)->where('code', 'IVA16')->firstOrFail();
        $genericBrand = Brand::where('company_id', $company->id)->where('name', 'Genérica')->firstOrFail();

        $bebidas = Category::where('company_id', $company->id)->where('name', 'Bebidas')->firstOrFail();
        $abarrotes = Category::where('company_id', $company->id)->where('name', 'Abarrotes')->firstOrFail();
        $farmacia = Category::where('company_id', $company->id)->where('name', 'Farmacia')->firstOrFail();
        $pasteleria = Category::where('company_id', $company->id)->where('name', 'Pastelería')->firstOrFail();
        $ferreteria = Category::firstOrCreate(
            ['company_id' => $company->id, 'parent_id' => null, 'name' => 'Ferretería'],
            ['slug' => 'ferreteria', 'sort_order' => 4, 'status' => Status::Active],
        );

        Tax::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'IVA8'],
            ['name' => 'IVA 8% frontera', 'rate' => 8, 'type' => TaxType::Percentage, 'included_in_price' => true, 'status' => Status::Active],
        );

        $recorder = app(RecordInventoryMovementAction::class);

        $catalog = [
            ['sku' => 'AGUA-1L', 'name' => 'Agua purificada 1L', 'category' => $bebidas, 'unit' => $liter, 'cost' => '6.0000', 'price' => '10.0000', 'barcode' => '7501234567901', 'stock' => 500],
            ['sku' => 'GALLETAS-MARIA', 'name' => 'Galletas María 170g', 'category' => $abarrotes, 'unit' => $piece, 'cost' => '14.0000', 'price' => '22.0000', 'barcode' => '7501234567902', 'stock' => 350],
            ['sku' => 'ACEITE-1L', 'name' => 'Aceite vegetal 1L', 'category' => $abarrotes, 'unit' => $piece, 'cost' => '28.0000', 'price' => '42.0000', 'barcode' => '7501234567903', 'stock' => 250],
            ['sku' => 'CAFE-SOLUBLE', 'name' => 'Café soluble 200g', 'category' => $abarrotes, 'unit' => $piece, 'cost' => '55.0000', 'price' => '85.0000', 'barcode' => '7501234567904', 'stock' => 200],
            ['sku' => 'VITAMINA-C', 'name' => 'Vitamina C 500mg (30 tabletas)', 'category' => $farmacia, 'unit' => $piece, 'cost' => '35.0000', 'price' => '58.0000', 'barcode' => '7501234567905', 'stock' => 280],
            ['sku' => 'ALCOHOL-GEL', 'name' => 'Gel antibacterial 250ml', 'category' => $farmacia, 'unit' => $piece, 'cost' => '18.0000', 'price' => '32.0000', 'barcode' => '7501234567906', 'stock' => 320],
            ['sku' => 'PASTEL-CHOCO', 'name' => 'Rebanada de pastel de chocolate', 'category' => $pasteleria, 'unit' => $piece, 'cost' => '15.0000', 'price' => '35.0000', 'barcode' => '7501234567907', 'stock' => 180],
            ['sku' => 'PAN-DULCE', 'name' => 'Pan dulce surtido (pieza)', 'category' => $pasteleria, 'unit' => $piece, 'cost' => '4.0000', 'price' => '9.0000', 'barcode' => '7501234567908', 'stock' => 450],
            ['sku' => 'FOCO-LED-9W', 'name' => 'Foco LED 9W', 'category' => $ferreteria, 'unit' => $piece, 'cost' => '22.0000', 'price' => '38.0000', 'barcode' => '7501234567909', 'stock' => 260],
            ['sku' => 'CINTA-AISLAR', 'name' => 'Cinta de aislar 18m', 'category' => $ferreteria, 'unit' => $piece, 'cost' => '8.0000', 'price' => '15.0000', 'barcode' => '7501234567910', 'stock' => 400],
        ];

        foreach ($catalog as $data) {
            $product = Product::firstOrCreate(
                ['company_id' => $company->id, 'sku' => $data['sku']],
                [
                    'category_id' => $data['category']->id,
                    'brand_id' => $genericBrand->id,
                    'unit_id' => $data['unit']->id,
                    'tax_id' => $iva->id,
                    'name' => $data['name'],
                    'slug' => str($data['name'])->slug(),
                    'product_type' => ProductType::Physical,
                    'tracking_type' => TrackingType::Simple,
                    'cost' => $data['cost'],
                    'sale_price' => $data['price'],
                    'minimum_stock' => '10',
                    'visible_in_pos' => true,
                    'is_favorite' => false,
                    'status' => Status::Active,
                ],
            );

            ProductBarcode::firstOrCreate(
                ['company_id' => $company->id, 'barcode' => $data['barcode']],
                ['product_id' => $product->id, 'type' => BarcodeType::Ean13, 'is_primary' => true, 'quantity_multiplier' => 1],
            );

            $recorder->execute([
                'company_id' => $company->id,
                'branch_id' => $branchCentro->id,
                'warehouse_id' => $warehouseCentro->id,
                'product_id' => $product->id,
                'movement_type' => InventoryMovementType::Initial,
                'quantity' => (string) $data['stock'],
                'unit_cost' => $data['cost'],
                'reason' => 'Inventario inicial (seeder demo)',
                'performed_by' => $admin->id,
            ]);

            $recorder->execute([
                'company_id' => $company->id,
                'branch_id' => $branchNorth->id,
                'warehouse_id' => $warehouseNorth->id,
                'product_id' => $product->id,
                'movement_type' => InventoryMovementType::Initial,
                'quantity' => (string) (int) round($data['stock'] * 0.4),
                'unit_cost' => $data['cost'],
                'reason' => 'Inventario inicial (seeder demo)',
                'performed_by' => $admin->id,
            ]);
        }

        Product::query()->whereIn('sku', ['AGUA-1L', 'GALLETAS-MARIA', 'PAN-DULCE'])->update(['is_favorite' => true]);
    }
}
