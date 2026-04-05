<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 4D: CSBG Expenditures (Module 2, Section A)
        Schema::create('csbg_expenditures', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('reporting_period');
            $table->string('domain');
            $table->decimal('csbg_funds', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year', 'domain']);
        });

        // 4E: Community Initiatives (Module 3, Section A)
        Schema::create('community_initiatives', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('year_number')->default(1);
            $table->text('problem_statement')->nullable();
            $table->text('goal_statement')->nullable();
            $table->string('domain');
            $table->string('identified_community')->nullable();
            $table->string('expected_duration')->nullable();
            $table->string('partnership_type')->nullable();
            $table->text('partners')->nullable();
            $table->json('strategies')->nullable();
            $table->string('progress_status')->nullable();
            $table->text('impact_narrative')->nullable();
            $table->string('final_status')->nullable();
            $table->text('lessons_learned')->nullable();
            $table->unsignedSmallInteger('fiscal_year');
            $table->timestamps();
            $table->softDeletes();
        });

        // 4F: FNPI Targets
        Schema::create('fnpi_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('npi_indicator_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedInteger('target_count')->default(0);
            $table->timestamps();

            $table->unique(['npi_indicator_id', 'fiscal_year']);
        });

        // 4G: CSBG SRV Categories (Module 4 Section B)
        Schema::create('csbg_srv_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('domain');
            $table->string('group_name');
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Pivot: Service ↔ SRV Category
        Schema::create('service_srv_category', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('csbg_srv_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['service_id', 'csbg_srv_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_srv_category');
        Schema::dropIfExists('csbg_srv_categories');
        Schema::dropIfExists('fnpi_targets');
        Schema::dropIfExists('community_initiatives');
        Schema::dropIfExists('csbg_expenditures');
    }
};
