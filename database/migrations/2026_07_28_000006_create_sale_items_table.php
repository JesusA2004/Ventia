<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('product_lot_id')->nullable()->constrained('product_lots')->nullOnDelete();
            // Snapshots: the sale must remain readable even if the product is
            // later renamed, re-SKU'd, or deleted.
            $table->string('product_name_snapshot');
            $table->string('sku_snapshot');
            $table->string('barcode_snapshot')->nullable();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('original_unit_price', 14, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('discount_amount', 14, 4)->default(0);
            $table->decimal('tax_rate', 10, 4)->default(0);
            $table->decimal('tax_amount', 14, 4)->default(0);
            $table->decimal('subtotal', 14, 4);
            $table->decimal('total', 14, 4);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sale_id']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
