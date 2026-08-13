<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Dial code kept separate from the local phone number (e.g. "+52")
            // so a Mexican LADA is never confused with the country code, and
            // so national-length validation (10 digits for MX) only applies
            // when the code is actually +52.
            $table->string('phone_country_code', 5)->default('+52')->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('phone_country_code');
        });
    }
};
