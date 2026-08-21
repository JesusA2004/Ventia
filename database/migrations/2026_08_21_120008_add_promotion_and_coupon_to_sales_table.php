<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot of whichever promotion/coupon applied at checkout, so a sale's
 * history stays accurate even if the promotion/coupon is later edited or
 * deleted. sales.discount_total (unchanged) still holds the true total
 * discount — these are additive breakdown columns, not a new source of
 * truth, so no existing report/export needs to change to keep working.
 * Only one of each may apply per sale in this version — see
 * PromotionEligibilityService for why stacking multiple promotions isn't
 * supported yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('promotion_id')->nullable()->after('discount_total')->constrained()->nullOnDelete();
            $table->string('promotion_name_snapshot')->nullable()->after('promotion_id');
            $table->decimal('promotion_discount_amount', 14, 4)->default(0)->after('promotion_name_snapshot');
            $table->foreignId('coupon_id')->nullable()->after('promotion_discount_amount')->constrained()->nullOnDelete();
            $table->string('coupon_code_snapshot')->nullable()->after('coupon_id');
            $table->decimal('coupon_discount_amount', 14, 4)->default(0)->after('coupon_code_snapshot');

            $table->index(['company_id', 'promotion_id']);
            $table->index(['company_id', 'coupon_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'promotion_id']);
            $table->dropIndex(['company_id', 'coupon_id']);
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['promotion_name_snapshot', 'promotion_discount_amount', 'coupon_code_snapshot', 'coupon_discount_amount']);
        });
    }
};
