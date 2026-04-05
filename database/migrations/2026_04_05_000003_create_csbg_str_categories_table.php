<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('csbg_str_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('group_code')->index();
            $table->string('group_name');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('community_initiative_str_category', function (Blueprint $table) {
            $table->foreignId('community_initiative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('csbg_str_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['community_initiative_id', 'csbg_str_category_id'], 'ci_str_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_initiative_str_category');
        Schema::dropIfExists('csbg_str_categories');
    }
};
