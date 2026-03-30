<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Demographic indexes for NPI report breakdowns.
        // These support GROUP BY race/gender/ethnicity in the demographic query.
        if (! $this->hasIndex('clients', 'clients_race_index')) {
            Schema::table('clients', fn (Blueprint $table) => $table->index('race'));
        }

        if (! $this->hasIndex('clients', 'clients_gender_index')) {
            Schema::table('clients', fn (Blueprint $table) => $table->index('gender'));
        }

        if (! $this->hasIndex('clients', 'clients_ethnicity_index')) {
            Schema::table('clients', fn (Blueprint $table) => $table->index('ethnicity'));
        }

        // Reverse lookup on pivot: find which indicators a service maps to.
        if (! $this->hasIndex('npi_indicator_service', 'npi_indicator_service_service_id_index')) {
            Schema::table('npi_indicator_service', fn (Blueprint $table) => $table->index('service_id'));
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['race']);
            $table->dropIndex(['gender']);
            $table->dropIndex(['ethnicity']);
        });

        if ($this->hasIndex('npi_indicator_service', 'npi_indicator_service_service_id_index')) {
            Schema::table('npi_indicator_service', fn (Blueprint $table) => $table->dropIndex(['service_id']));
        }
    }

    protected function hasIndex(string $table, string $indexName): bool
    {
        if (config('database.default') === 'sqlite') {
            return (bool) \Illuminate\Support\Facades\DB::selectOne(
                "SELECT 1 FROM sqlite_master WHERE type='index' AND name=? AND tbl_name=?",
                [$indexName, $table],
            );
        }

        return \Illuminate\Support\Facades\Schema::hasIndex($table, $indexName);
    }
};
