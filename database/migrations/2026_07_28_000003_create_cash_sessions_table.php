<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('register_id')->constrained('registers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('open');
            $table->timestamp('opened_at');
            $table->decimal('opening_amount', 14, 4);
            $table->decimal('expected_cash', 14, 4)->nullable();
            $table->decimal('counted_cash', 14, 4)->nullable();
            $table->decimal('difference', 14, 4)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamps();

            // MySQL treats every NULL as distinct in a unique index, so a
            // partial-unique-on-status constraint isn't possible here; "one
            // open session per register/user" is enforced in
            // OpenCashSessionAction via lockForUpdate() + an explicit check,
            // same trade-off already documented on inventory_balances.
            $table->index(['register_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['company_id', 'branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
