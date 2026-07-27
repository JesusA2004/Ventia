<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('register_id')->constrained('registers')->cascadeOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('folio');
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 14, 4)->default(0);
            $table->decimal('discount_total', 14, 4)->default(0);
            $table->decimal('tax_total', 14, 4)->default(0);
            $table->decimal('total', 14, 4)->default(0);
            $table->decimal('cost_total', 14, 4)->default(0);
            $table->decimal('profit_total', 14, 4)->default(0);
            $table->decimal('amount_received', 14, 4)->default(0);
            $table->decimal('change_amount', 14, 4)->default(0);
            // Idempotent-checkout guard: the POS sends one UUID per checkout
            // attempt, so a duplicated request (double click, retry) resolves
            // to the same sale instead of creating a second one.
            $table->uuid('checkout_uuid')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'folio']);
            $table->unique(['company_id', 'checkout_uuid']);
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['company_id', 'cash_session_id']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
