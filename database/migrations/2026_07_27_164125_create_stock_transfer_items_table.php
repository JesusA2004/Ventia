<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants');
            $table->foreignId('product_lot_id')->nullable()->constrained('product_lots');
            $table->decimal('quantity_requested', 14, 4);
            $table->decimal('quantity_shipped', 14, 4)->nullable();
            $table->decimal('quantity_received', 14, 4)->nullable();
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->timestamps();

            $table->index(['stock_transfer_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};
