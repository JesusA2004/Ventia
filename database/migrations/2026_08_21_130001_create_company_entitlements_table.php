<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per company: the single source of truth FeatureGateService
        // reads from. A company with no row is treated as Basic (see
        // CompanyEntitlement::planFor()) — activating Pro is what creates it.
        Schema::create('company_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('plan', 20)->default('basic');
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('license_key_id')->nullable()->constrained('license_keys')->nullOnDelete();
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_entitlements');
    }
};
