<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cnpi_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->index();
            $table->string('indicator_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('cnpi_type')->default('count_of_change');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cnpi_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cnpi_indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_initiative_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->string('identified_community')->nullable();
            $table->decimal('target', 12, 2)->nullable();
            $table->decimal('actual_result', 12, 2)->nullable();
            $table->decimal('performance_accuracy', 5, 2)->nullable();
            $table->decimal('baseline_value', 12, 2)->nullable();
            $table->decimal('expected_change_pct', 5, 2)->nullable();
            $table->decimal('actual_change_pct', 5, 2)->nullable();
            $table->string('data_source')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['cnpi_indicator_id', 'fiscal_year'], 'idx_cnpi_result_indicator_fy');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cnpi_results');
        Schema::dropIfExists('cnpi_indicators');
    }
};
