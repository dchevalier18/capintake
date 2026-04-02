<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypt PII fields at rest: date_of_birth (clients, household_members)
 * and income amounts (income_records).
 *
 * Adds birth_year columns for age-range queries (NPI reporting) since
 * encrypted DOB cannot be used in SQL expressions.
 *
 * IMPORTANT: For existing databases with data, this migration encrypts
 * all existing values in-place. For fresh installs, run migrate:fresh.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Clients: encrypt date_of_birth, add birth_year ---
        $this->encryptDateOfBirth('clients');

        // --- Household Members: encrypt date_of_birth, add birth_year ---
        $this->encryptDateOfBirth('household_members');

        // --- Income Records: encrypt amount and annual_amount ---
        $this->encryptIncomeAmounts();
    }

    public function down(): void
    {
        // Reverse income_records
        $this->decryptIncomeAmounts();

        // Reverse household_members
        $this->decryptDateOfBirth('household_members');

        // Reverse clients
        $this->decryptDateOfBirth('clients');
    }

    private function encryptDateOfBirth(string $table): void
    {
        // Add birth_year column
        Schema::table($table, function (Blueprint $t) {
            $t->unsignedSmallInteger('birth_year')->nullable()->after('date_of_birth');
            $t->index('birth_year');
        });

        // Populate birth_year from existing date_of_birth and encrypt DOB
        DB::table($table)->whereNotNull('date_of_birth')->orderBy('id')->chunk(100, function ($rows) use ($table) {
            foreach ($rows as $row) {
                $dob = $row->date_of_birth;
                $birthYear = (int) date('Y', strtotime($dob));
                $encrypted = Crypt::encryptString($dob);

                DB::table($table)->where('id', $row->id)->update([
                    'date_of_birth' => $encrypted,
                    'birth_year' => $birthYear,
                ]);
            }
        });

        // Now change column type from date to text
        // Drop the date_of_birth index first (clients table has one)
        if ($table === 'clients') {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['date_of_birth']);
            });
        }

        Schema::table($table, function (Blueprint $t) {
            $t->text('date_of_birth')->nullable()->change();
        });
    }

    private function decryptDateOfBirth(string $table): void
    {
        // Decrypt existing values back to plain dates
        DB::table($table)->whereNotNull('date_of_birth')->orderBy('id')->chunk(100, function ($rows) use ($table) {
            foreach ($rows as $row) {
                try {
                    $decrypted = Crypt::decryptString($row->date_of_birth);
                    DB::table($table)->where('id', $row->id)->update([
                        'date_of_birth' => $decrypted,
                    ]);
                } catch (\Throwable) {
                    // Already plain text, skip
                }
            }
        });

        Schema::table($table, function (Blueprint $t) {
            $t->date('date_of_birth')->nullable()->change();
        });

        if ($table === 'clients') {
            Schema::table($table, function (Blueprint $t) {
                $t->index('date_of_birth');
            });
        }

        Schema::table($table, function (Blueprint $t) {
            $t->dropIndex(['birth_year']);
            $t->dropColumn('birth_year');
        });
    }

    private function encryptIncomeAmounts(): void
    {
        // Encrypt existing values
        DB::table('income_records')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                $updates = [];
                if ($row->amount !== null) {
                    $updates['amount'] = Crypt::encryptString((string) $row->amount);
                }
                if ($row->annual_amount !== null) {
                    $updates['annual_amount'] = Crypt::encryptString((string) $row->annual_amount);
                }
                if (! empty($updates)) {
                    DB::table('income_records')->where('id', $row->id)->update($updates);
                }
            }
        });

        // Change column types to text
        Schema::table('income_records', function (Blueprint $t) {
            $t->text('amount')->nullable()->change();
            $t->text('annual_amount')->nullable()->change();
        });
    }

    private function decryptIncomeAmounts(): void
    {
        DB::table('income_records')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                $updates = [];
                if ($row->amount !== null) {
                    try {
                        $updates['amount'] = Crypt::decryptString($row->amount);
                    } catch (\Throwable) {
                        // Already plain text
                    }
                }
                if ($row->annual_amount !== null) {
                    try {
                        $updates['annual_amount'] = Crypt::decryptString($row->annual_amount);
                    } catch (\Throwable) {
                        // Already plain text
                    }
                }
                if (! empty($updates)) {
                    DB::table('income_records')->where('id', $row->id)->update($updates);
                }
            }
        });

        Schema::table('income_records', function (Blueprint $t) {
            $t->decimal('amount', 10, 2)->change();
            $t->decimal('annual_amount', 10, 2)->nullable()->change();
        });
    }
};
