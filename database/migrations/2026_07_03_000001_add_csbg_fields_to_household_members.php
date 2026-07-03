<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('household_members', function (Blueprint $table): void {
            // CSBG Module 4 Section C individual characteristics, parallel to
            // the columns on clients. The legacy health_insurance column stays
            // for backward compatibility but is superseded by these two.
            $table->string('health_insurance_status')->nullable()->after('health_insurance');
            $table->string('health_insurance_source')->nullable()->after('health_insurance_status');
            $table->string('military_status')->nullable()->after('health_insurance_source');
        });
    }

    public function down(): void
    {
        Schema::table('household_members', function (Blueprint $table): void {
            $table->dropColumn(['health_insurance_status', 'health_insurance_source', 'military_status']);
        });
    }
};
