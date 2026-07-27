<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Query-side projection of inventory_movements. Never written to directly;
        // only App\Services\Inventory\InventoryBalanceService (behind a row lock)
        // is allowed to touch this table. No DB-level unique constraint on the
        // (warehouse, product, variant, lot) tuple is possible here because
        // MySQL treats each NULL as distinct in a unique index, so the single
        // writer + lockForUpdate() discipline is what guarantees one row per
        // tuple in practice.
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_lot_id')->nullable()->constrained('product_lots')->cascadeOnDelete();
            $table->decimal('quantity', 14, 4)->default(0);
            $table->decimal('average_cost', 14, 4)->default(0);
            $table->timestamps();

            $table->index(['warehouse_id', 'product_id', 'product_variant_id', 'product_lot_id'], 'inv_bal_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
