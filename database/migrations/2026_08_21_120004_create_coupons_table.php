<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // Normalized (uppercased, trimmed) before saving — see Coupon model.
            $table->string('code', 40);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 20);
            $table->decimal('value', 14, 4);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->decimal('min_purchase_amount', 14, 4)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->boolean('combinable')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
