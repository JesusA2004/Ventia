<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->decimal('amount', 14, 4);
            $table->string('reference')->nullable();
            $table->string('authorization_number')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('bank')->nullable();
            $table->string('terminal')->nullable();
            $table->string('status', 20)->default('captured');
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->index(['sale_id']);
            $table->index(['payment_method_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
