<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            // Supplier catalog is built in a later phase (Compras y proveedores);
            // kept as a plain nullable column, no FK, until that model exists.
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('lot_number');
            $table->date('manufacture_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->date('received_at')->nullable();
            $table->decimal('cost', 14, 4)->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'product_id', 'lot_number']);
            $table->index(['company_id', 'expiration_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_lots');
    }
};
