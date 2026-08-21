<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * No rows for a promotion = applies to every branch. Any row restricts it
 * to that set — same "empty means unrestricted" convention as product_prices'
 * nullable branch_id, just expressed as a pivot since a promotion can be
 * restricted to more than one specific branch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_branch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->unique(['promotion_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_branch');
    }
};
