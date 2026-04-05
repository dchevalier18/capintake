<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 4A: Extend clients table with CSBG Module 4 Section C fields
        Schema::table('clients', function (Blueprint $table) {
            $table->string('education_level')->nullable()->after('preferred_language');
            $table->string('health_insurance_status')->nullable()->after('education_level');
            $table->string('health_insurance_source')->nullable()->after('health_insurance_status');
            $table->string('military_status')->nullable()->after('health_insurance_source');
            $table->string('employment_status')->nullable()->after('military_status');
            $table->boolean('is_disconnected_youth')->default(false)->after('is_disabled');
        });

        // Backfill military_status from is_veteran
        DB::table('clients')
            ->where('is_veteran', true)
            ->whereNull('military_status')
            ->update(['military_status' => 'veteran']);

        // 4B: Create client_non_cash_benefits table
        Schema::create('client_non_cash_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('benefit_type');
            $table->date('effective_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('client_id');
            $table->index('benefit_type');
        });

        // 4C: Add household_type to households
        Schema::table('households', function (Blueprint $table) {
            $table->string('household_type')->nullable()->after('housing_type');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'education_level', 'health_insurance_status', 'health_insurance_source',
                'military_status', 'employment_status', 'is_disconnected_youth',
            ]);
        });

        Schema::dropIfExists('client_non_cash_benefits');

        Schema::table('households', function (Blueprint $table) {
            $table->dropColumn('household_type');
        });
    }
};
