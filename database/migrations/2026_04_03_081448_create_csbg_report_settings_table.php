<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('csbg_report_settings', function (Blueprint $table) {
            $table->id();
            $table->string('entity_name');
            $table->string('state', 2);
            $table->string('uei')->nullable();
            $table->string('reporting_period')->default('oct_sep');
            $table->unsignedSmallInteger('current_fiscal_year');
            $table->decimal('total_csbg_allocation', 12, 2)->nullable();
            $table->json('additional_settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('csbg_report_settings');
    }
};
