<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('npi_indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('service_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('in_progress')->index();
            $table->date('achieved_date')->nullable()->index();
            $table->date('target_date')->nullable();
            $table->string('baseline_value')->nullable();
            $table->string('result_value')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['npi_indicator_id', 'fiscal_year', 'status'], 'idx_outcome_npi_fy_status');
            $table->index(['client_id', 'npi_indicator_id'], 'idx_outcome_client_indicator');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outcomes');
    }
};
