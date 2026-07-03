<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\IntakeStatus;
use App\Models\Client;
use App\Models\Household;
use App\Models\LookupValue;
use Illuminate\Support\Facades\DB;

/**
 * Imports clients from CSV exports of other systems (CAP60, empowOR,
 * spreadsheets). Maps arbitrary CSV columns to client/household fields,
 * normalizes demographic values to the CSBG lookup keys, skips duplicates
 * using the same name + SSN-last-four logic as the intake wizard, and
 * supports a dry run that reports what would happen without writing.
 */
class ClientImportService
{
    /**
     * Importable target fields (field => label). Lookup-backed fields are
     * normalized against their lookup category.
     */
    public const TARGET_FIELDS = [
        'first_name' => 'First Name',
        'middle_name' => 'Middle Name',
        'last_name' => 'Last Name',
        'date_of_birth' => 'Date of Birth',
        'ssn' => 'SSN',
        'phone' => 'Phone',
        'email' => 'Email',
        'gender' => 'Gender',
        'race' => 'Race',
        'ethnicity' => 'Ethnicity',
        'education_level' => 'Education Level',
        'employment_status' => 'Work Status',
        'military_status' => 'Military Status',
        'health_insurance_status' => 'Health Insurance (yes/no)',
        'health_insurance_source' => 'Health Insurance Source',
        'is_veteran' => 'Veteran (yes/no)',
        'is_disabled' => 'Disabled (yes/no)',
        'preferred_language' => 'Preferred Language',
        'address_line_1' => 'Street Address',
        'address_line_2' => 'Address Line 2',
        'city' => 'City',
        'state' => 'State',
        'zip' => 'ZIP',
        'county' => 'County',
        'housing_type' => 'Housing Type',
        'household_type' => 'Household Type',
        'household_size' => 'Household Size',
    ];

    /** Fields normalized against a lookup category. */
    protected const LOOKUP_FIELDS = [
        'gender' => 'gender',
        'race' => 'race',
        'ethnicity' => 'ethnicity',
        'education_level' => 'education_level',
        'employment_status' => 'employment_status',
        'military_status' => 'military_status',
        'health_insurance_status' => 'health_insurance_status',
        'health_insurance_source' => 'health_insurance_source',
        'housing_type' => 'housing_type',
        'household_type' => 'household_type',
    ];

    /** Common value synonyms from other systems, per field. */
    protected const SYNONYMS = [
        'gender' => [
            'm' => 'male', 'f' => 'female', 'man' => 'male', 'woman' => 'female',
        ],
        'race' => [
            'black' => 'black_african_american',
            'african american' => 'black_african_american',
            'black/african american' => 'black_african_american',
            'american indian' => 'american_indian_alaska_native',
            'native american' => 'american_indian_alaska_native',
            'pacific islander' => 'native_hawaiian_pacific_islander',
            'native hawaiian' => 'native_hawaiian_pacific_islander',
            'multiracial' => 'multi_race',
            'two or more races' => 'multi_race',
            'multi-race' => 'multi_race',
        ],
        'ethnicity' => [
            'hispanic' => 'hispanic_latino',
            'latino' => 'hispanic_latino',
            'hispanic or latino' => 'hispanic_latino',
            'non-hispanic' => 'not_hispanic_latino',
            'not hispanic' => 'not_hispanic_latino',
            'not hispanic or latino' => 'not_hispanic_latino',
        ],
        'housing_type' => [
            'owner' => 'own', 'owned' => 'own', 'homeowner' => 'own',
            'renter' => 'rent', 'rented' => 'rent', 'rental' => 'rent',
        ],
        'health_insurance_status' => [
            'y' => 'yes', 'n' => 'no', 'insured' => 'yes', 'uninsured' => 'no',
        ],
        'military_status' => [
            'vet' => 'veteran',
            'active duty' => 'active',
            'none' => 'never_served',
            'no military service' => 'never_served',
        ],
    ];

    /**
     * Parse a CSV file into [headers, rows].
     *
     * @return array{0: list<string>, 1: list<list<string|null>>}
     */
    public function parse(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open CSV file: {$path}");
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);

            throw new \RuntimeException('CSV file is empty.');
        }

        $headers = array_map(fn ($h) => trim((string) $h), $headers);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($v) => $v !== null && trim((string) $v) !== '')) === 0) {
                continue; // skip blank lines
            }
            $rows[] = $row;
        }
        fclose($handle);

        return [$headers, $rows];
    }

    /**
     * Guess a column mapping from CSV headers.
     *
     * @param  list<string>  $headers
     * @return array<int, string|null> column index => target field
     */
    public function guessMapping(array $headers): array
    {
        $aliases = [
            'first_name' => ['first name', 'firstname', 'first', 'fname', 'given name'],
            'middle_name' => ['middle name', 'middlename', 'middle', 'mi'],
            'last_name' => ['last name', 'lastname', 'last', 'lname', 'surname', 'family name'],
            'date_of_birth' => ['date of birth', 'dob', 'birth date', 'birthdate'],
            'ssn' => ['ssn', 'social security', 'social security number', 'ssn last 4', 'ssn4'],
            'phone' => ['phone', 'phone number', 'telephone', 'primary phone', 'cell'],
            'email' => ['email', 'e-mail', 'email address'],
            'gender' => ['gender', 'sex'],
            'race' => ['race'],
            'ethnicity' => ['ethnicity'],
            'education_level' => ['education', 'education level', 'highest education'],
            'employment_status' => ['employment', 'employment status', 'work status'],
            'military_status' => ['military', 'military status', 'veteran status'],
            'health_insurance_status' => ['health insurance', 'insured', 'insurance'],
            'health_insurance_source' => ['insurance source', 'insurance type', 'health insurance source'],
            'is_veteran' => ['veteran', 'is veteran'],
            'is_disabled' => ['disabled', 'disability', 'disabling condition'],
            'preferred_language' => ['language', 'preferred language', 'primary language'],
            'address_line_1' => ['address', 'street address', 'address 1', 'address line 1', 'street'],
            'address_line_2' => ['address 2', 'address line 2', 'apt', 'unit'],
            'city' => ['city', 'town'],
            'state' => ['state', 'st'],
            'zip' => ['zip', 'zip code', 'zipcode', 'postal code'],
            'county' => ['county'],
            'housing_type' => ['housing', 'housing type', 'tenure'],
            'household_type' => ['household type', 'family type'],
            'household_size' => ['household size', 'family size', 'hh size'],
        ];

        $mapping = [];
        $used = [];

        foreach ($headers as $index => $header) {
            $normalized = strtolower(trim($header));
            $mapping[$index] = null;

            foreach ($aliases as $field => $names) {
                if (in_array($normalized, $names, true) && ! in_array($field, $used, true)) {
                    $mapping[$index] = $field;
                    $used[] = $field;

                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * Import rows using a column mapping.
     *
     * @param  list<list<string|null>>  $rows
     * @param  array<int, string|null>  $mapping  column index => target field
     * @return array{total: int, created: int, skipped_duplicates: int, errors: array<int, string>}
     */
    public function import(array $rows, array $mapping, bool $dryRun = false): array
    {
        $result = [
            'total' => count($rows),
            'created' => 0,
            'skipped_duplicates' => 0,
            'errors' => [],
        ];

        if (! in_array('first_name', $mapping, true) || ! in_array('last_name', $mapping, true)) {
            throw new \InvalidArgumentException('The mapping must include First Name and Last Name columns.');
        }

        foreach ($rows as $rowIndex => $row) {
            $data = [];
            foreach ($mapping as $columnIndex => $field) {
                if ($field !== null) {
                    $value = trim((string) ($row[$columnIndex] ?? ''));
                    $data[$field] = $value === '' ? null : $value;
                }
            }

            $rowNumber = $rowIndex + 2; // 1-based + header row

            if (empty($data['first_name']) || empty($data['last_name'])) {
                $result['errors'][$rowNumber] = 'Missing first or last name.';

                continue;
            }

            $data = $this->normalizeRow($data, $result['errors'], $rowNumber);

            if ($this->isDuplicate($data)) {
                $result['skipped_duplicates']++;

                continue;
            }

            if (! $dryRun) {
                try {
                    $this->createClient($data);
                } catch (\Throwable $e) {
                    $result['errors'][$rowNumber] = 'Failed to save: '.$e->getMessage();

                    continue;
                }
            }

            $result['created']++;
        }

        return $result;
    }

    /**
     * Normalize lookup values, booleans, and dates in a mapped row.
     */
    protected function normalizeRow(array $data, array &$errors, int $rowNumber): array
    {
        foreach (self::LOOKUP_FIELDS as $field => $category) {
            if (! empty($data[$field])) {
                $normalized = $this->normalizeLookupValue($category, $field, $data[$field]);
                if ($normalized === null) {
                    $errors[$rowNumber] = ($errors[$rowNumber] ?? '')."Unrecognized {$field} value \"{$data[$field]}\" left blank. ";
                }
                $data[$field] = $normalized;
            }
        }

        foreach (['is_veteran', 'is_disabled'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = in_array(strtolower((string) $data[$field]), ['1', 'y', 'yes', 'true'], true);
            }
        }

        if (! empty($data['date_of_birth'])) {
            $timestamp = strtotime($data['date_of_birth']);
            if ($timestamp === false) {
                $errors[$rowNumber] = ($errors[$rowNumber] ?? '')."Unparseable date of birth \"{$data['date_of_birth']}\" left blank. ";
                $data['date_of_birth'] = null;
            } else {
                $data['date_of_birth'] = date('Y-m-d', $timestamp);
            }
        }

        return $data;
    }

    /**
     * Resolve a raw CSV value to a lookup key: exact key, label match,
     * then per-field synonyms.
     */
    public function normalizeLookupValue(string $category, string $field, string $raw): ?string
    {
        $needle = strtolower(trim($raw));

        $values = LookupValue::whereHas('category', fn ($q) => $q->where('key', $category))->get();

        foreach ($values as $value) {
            if (strtolower($value->key) === $needle || strtolower($value->label) === $needle) {
                return $value->key;
            }
        }

        $synonym = self::SYNONYMS[$field][$needle] ?? null;
        if ($synonym && $values->contains(fn ($v) => $v->key === $synonym)) {
            return $synonym;
        }

        return null;
    }

    /**
     * Duplicate check mirroring the intake wizard: exact name match, or a
     * matching SSN last four.
     */
    protected function isDuplicate(array $data): bool
    {
        $query = Client::query()->complete()->where(function ($q) use ($data): void {
            $q->where(function ($sub) use ($data): void {
                $sub->whereRaw('LOWER(first_name) = ?', [strtolower($data['first_name'])])
                    ->whereRaw('LOWER(last_name) = ?', [strtolower($data['last_name'])]);
            });

            $digits = preg_replace('/\D/', '', (string) ($data['ssn'] ?? ''));
            if (strlen($digits) >= 4) {
                $q->orWhere('ssn_last_four', substr($digits, -4));
            }
        });

        return $query->exists();
    }

    /**
     * Create the household + client for a normalized row.
     */
    protected function createClient(array $data): Client
    {
        return DB::transaction(function () use ($data): Client {
            $household = Household::create([
                'address_line_1' => $data['address_line_1'] ?? 'Unknown',
                'address_line_2' => $data['address_line_2'] ?? null,
                'city' => $data['city'] ?? 'Unknown',
                'state' => $data['state'] ?? 'PA',
                'zip' => $data['zip'] ?? '00000',
                'county' => $data['county'] ?? null,
                'housing_type' => $data['housing_type'] ?? null,
                'household_type' => $data['household_type'] ?? null,
                'household_size' => max(1, (int) ($data['household_size'] ?? 1)),
            ]);

            $ssnDigits = preg_replace('/\D/', '', (string) ($data['ssn'] ?? ''));

            return Client::create([
                'household_id' => $household->id,
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'ssn_encrypted' => strlen($ssnDigits) >= 9 ? $ssnDigits : null,
                'ssn_last_four' => strlen($ssnDigits) >= 4 ? substr($ssnDigits, -4) : null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'gender' => $data['gender'] ?? null,
                'race' => $data['race'] ?? null,
                'ethnicity' => $data['ethnicity'] ?? null,
                'education_level' => $data['education_level'] ?? null,
                'employment_status' => $data['employment_status'] ?? null,
                'military_status' => $data['military_status'] ?? null,
                'health_insurance_status' => $data['health_insurance_status'] ?? null,
                'health_insurance_source' => $data['health_insurance_source'] ?? null,
                'is_veteran' => $data['is_veteran'] ?? false,
                'is_disabled' => $data['is_disabled'] ?? false,
                'preferred_language' => $data['preferred_language'] ?? 'en',
                'is_head_of_household' => true,
                'relationship_to_head' => 'self',
                'intake_status' => IntakeStatus::Complete,
                'intake_step' => 5,
            ]);
        });
    }
}
