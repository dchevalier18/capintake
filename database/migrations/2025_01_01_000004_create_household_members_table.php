<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('household_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('race')->nullable();
            $table->string('ethnicity')->nullable();
            $table->string('relationship_to_client'); // spouse, child, parent, sibling, grandchild, other
            $table->string('employment_status')->nullable();
            $table->boolean('is_veteran')->default(false);
            $table->boolean('is_disabled')->default(false);
            $table->boolean('is_student')->default(false);
            $table->string('education_level')->nullable(); // less_than_hs, hs_ged, some_college, associates, bachelors, graduate
            $table->string('health_insurance')->nullable(); // medicaid, medicare, employer, marketplace, none, other
            $table->timestamps();
            $table->softDeletes();

            $table->index('household_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_members');
    }
};
