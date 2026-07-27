<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units');
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('sku');
            $table->string('internal_code')->nullable();
            $table->string('product_type', 20)->default('physical');
            $table->string('tracking_type', 20)->default('simple');
            $table->string('image_path')->nullable();
            $table->decimal('cost', 14, 4)->default(0);
            $table->decimal('sale_price', 14, 4)->default(0);
            $table->decimal('minimum_price', 14, 4)->nullable();
            $table->decimal('wholesale_price', 14, 4)->nullable();
            $table->decimal('minimum_stock', 14, 4)->default(0);
            $table->decimal('maximum_stock', 14, 4)->nullable();
            $table->boolean('allows_negative_stock')->default(false);
            $table->boolean('visible_in_pos')->default(true);
            $table->boolean('is_favorite')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'sku']);
            $table->unique(['company_id', 'internal_code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'category_id']);
            $table->index(['company_id', 'visible_in_pos']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
