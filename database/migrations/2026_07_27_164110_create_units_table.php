<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('symbol', 10);
            $table->string('type', 20)->default('piece');
            $table->unsignedTinyInteger('decimal_places')->default(0);
            $table->boolean('allows_fraction')->default(false);
            $table->decimal('conversion_factor', 14, 6)->nullable();
            $table->foreignId('base_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
