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
        // 3A: Reset NPI data (pre-MVP, no production data to preserve)
        DB::table('npi_indicator_service')->truncate();
        DB::table('npi_indicators')->delete();
        DB::table('npi_goals')->delete();

        // 3B: Add FNPI columns to npi_indicators
        Schema::table('npi_indicators', function (Blueprint $table) {
            $table->foreignId('parent_indicator_id')
                ->nullable()
                ->after('npi_goal_id')
                ->constrained('npi_indicators')
                ->nullOnDelete();

            $table->string('indicator_type')->default('fnpi')->after('description');
            $table->boolean('is_aggregate')->default(false)->after('indicator_type');
        });
    }

    public function down(): void
    {
        Schema::table('npi_indicators', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_indicator_id');
            $table->dropColumn(['indicator_type', 'is_aggregate']);
        });
    }
};
