<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CSBG Annual Report 3.0 (OMB 0970-0492, approved Dec 2024) revises the
     * FNPI/SRV/STR/CNPI taxonomies. Existing rows become version 2.1; 3.0
     * arrives as new rows, so historical targets/outcomes/mappings that
     * reference taxonomy row ids are untouched.
     */
    public function up(): void
    {
        Schema::table('npi_indicators', function (Blueprint $table): void {
            $table->string('report_version', 8)->default('2.1')->index();
            $table->dropUnique(['indicator_code']);
            $table->unique(['indicator_code', 'report_version']);
        });

        Schema::table('csbg_srv_categories', function (Blueprint $table): void {
            $table->string('report_version', 8)->default('2.1')->index();
            $table->dropUnique(['code']);
            $table->unique(['code', 'report_version']);
        });

        Schema::table('csbg_str_categories', function (Blueprint $table): void {
            $table->string('report_version', 8)->default('2.1')->index();
            $table->dropUnique(['code']);
            $table->unique(['code', 'report_version']);
        });

        Schema::table('cnpi_indicators', function (Blueprint $table): void {
            $table->string('report_version', 8)->default('2.1')->index();
            $table->dropUnique(['indicator_code']);
            $table->unique(['indicator_code', 'report_version']);
        });

        Schema::table('csbg_report_settings', function (Blueprint $table): void {
            $table->string('report_version', 8)->default('2.1');
        });
    }

    public function down(): void
    {
        Schema::table('npi_indicators', function (Blueprint $table): void {
            $table->dropUnique(['indicator_code', 'report_version']);
            $table->unique(['indicator_code']);
            $table->dropColumn('report_version');
        });

        Schema::table('csbg_srv_categories', function (Blueprint $table): void {
            $table->dropUnique(['code', 'report_version']);
            $table->unique(['code']);
            $table->dropColumn('report_version');
        });

        Schema::table('csbg_str_categories', function (Blueprint $table): void {
            $table->dropUnique(['code', 'report_version']);
            $table->unique(['code']);
            $table->dropColumn('report_version');
        });

        Schema::table('cnpi_indicators', function (Blueprint $table): void {
            $table->dropUnique(['indicator_code', 'report_version']);
            $table->unique(['indicator_code']);
            $table->dropColumn('report_version');
        });

        Schema::table('csbg_report_settings', function (Blueprint $table): void {
            $table->dropColumn('report_version');
        });
    }
};
