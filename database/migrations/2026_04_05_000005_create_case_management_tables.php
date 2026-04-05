<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('status')->default('active')->index();
            $table->date('start_date');
            $table->date('target_completion_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('client_id');
        });

        Schema::create('case_plan_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('npi_indicator_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('not_started')->index();
            $table->date('target_date')->nullable();
            $table->date('achieved_date')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('case_plan_id');
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('referral_date');
            $table->string('referred_to_agency');
            $table->string('referred_to_contact')->nullable();
            $table->string('referred_to_phone')->nullable();
            $table->text('referral_reason')->nullable();
            $table->string('status')->default('pending')->index();
            $table->date('follow_up_date')->nullable()->index();
            $table->text('outcome')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('client_id');
        });

        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('follow_up_type');
            $table->date('scheduled_date')->index();
            $table->date('completed_date')->nullable();
            $table->string('status')->default('scheduled')->index();
            $table->text('notes')->nullable();
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamps();

            $table->index('client_id');
            $table->index(['related_type', 'related_id']);
        });

        Schema::create('self_sufficiency_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessment_date');
            $table->json('domain_scores');
            $table->unsignedSmallInteger('total_score')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'assessment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_sufficiency_assessments');
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('case_plan_goals');
        Schema::dropIfExists('case_plans');
    }
};
