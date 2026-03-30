<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('intake_status')->default('complete')->after('notes');
            $table->unsignedTinyInteger('intake_step')->default(1)->after('intake_status');
            $table->index('intake_status');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['intake_status']);
            $table->dropColumn(['intake_status', 'intake_step']);
        });
    }
};
