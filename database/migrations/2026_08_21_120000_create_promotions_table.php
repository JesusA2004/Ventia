<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 20);
            $table->decimal('value', 14, 4);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('active');
            // Whole-cart total the sale must reach for this promotion to be
            // eligible at all — independent of product/category scope below.
            $table->decimal('min_purchase_amount', 14, 4)->nullable();
            // Only meaningful alongside a product/category scope (promotion_
            // product/promotion_category): minimum matching-line quantity.
            $table->decimal('min_quantity', 14, 4)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            // Higher first when picking the single best eligible promotion —
            // see PromotionEligibilityService. Only one promotion applies per
            // sale in this version, so this never breaks a tie between two
            // promotions stacking; it only orders which one wins.
            $table->unsignedInteger('priority')->default(0);
            // Whether this promotion may still apply alongside a coupon. Two
            // automatic promotions never stack in this version (only the
            // highest-priority eligible one is picked), so this flag's only
            // effect today is promotion+coupon coexistence.
            $table->boolean('combinable')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
