<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_keys', function (Blueprint $table) {
            $table->id();
            // The plaintext serial is shown to the Superadmin exactly once at
            // generation time and never stored. code_hash is a keyed SHA-256
            // (see GenerateLicenseKeysAction) so a database leak alone can't
            // be redeemed; code_last4 is only for the masked display
            // "****-****-****-7KQ9" once a key exists.
            $table->string('code_hash', 64)->unique();
            $table->string('code_last4', 4);
            $table->string('plan', 20);
            $table->string('status', 20)->default('available');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('redeemed_at')->nullable();
            $table->foreignId('redeemed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            // Set when this key was generated to replace a revoked one
            // (controlled reissue — see RevokeLicenseKeyAction::reissue()).
            $table->foreignId('replaces_license_key_id')->nullable()->constrained('license_keys')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_keys');
    }
};
