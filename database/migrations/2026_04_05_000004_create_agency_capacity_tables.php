<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_capacity_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->string('metric_type')->index();
            $table->string('metric_key');
            $table->decimal('metric_value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year', 'metric_type', 'metric_key'], 'uniq_capacity_fy_type_key');
        });

        Schema::create('funding_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->string('source_type');
            $table->string('source_name');
            $table->string('cfda_number')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['fiscal_year', 'source_type'], 'idx_funding_fy_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funding_sources');
        Schema::dropIfExists('agency_capacity_metrics');
    }
};
