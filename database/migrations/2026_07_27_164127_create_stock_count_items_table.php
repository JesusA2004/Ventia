<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants');
            $table->foreignId('product_lot_id')->nullable()->constrained('product_lots');
            $table->decimal('expected_quantity', 14, 4);
            $table->decimal('counted_quantity', 14, 4)->nullable();
            $table->decimal('difference', 14, 4)->nullable();
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->timestamps();

            $table->index(['stock_count_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_items');
    }
};
