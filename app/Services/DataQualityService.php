<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DataQualityService
{
    /**
     * Required fields for a "complete" client record for CSBG reporting.
     */
    protected const REQUIRED_FIELDS = [
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'race',
        'ethnicity',
        'education_level',
        'employment_status',
        'health_insurance_status',
        'military_status',
        'household_id',
    ];

    /**
     * Compute completeness score for a single client.
     *
     * @return array{score: int, total_fields: int, completed_fields: int, missing: list<string>}
     */
    public function clientCompletenessScore(Client $client): array
    {
        $totalFields = count(self::REQUIRED_FIELDS);
        $missing = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            $value = $client->getAttribute($field);
            if ($value === null || $value === '') {
                $missing[] = $field;
            }
        }

        // Check for income records
        if ($client->incomeRecords()->count() === 0) {
            $missing[] = 'income_records';
            $totalFields++;
        } else {
            $totalFields++;
        }

        $completed = $totalFields - count($missing);
        $score = $totalFields > 0 ? (int) round(($completed / $totalFields) * 100) : 0;

        return [
            'score' => $score,
            'total_fields' => $totalFields,
            'completed_fields' => $completed,
            'missing' => $missing,
        ];
    }

    /**
     * Agency-wide completeness statistics.
     */
    public function agencyCompletenessOverview(): array
    {
        $clients = Client::complete()->get();
        $total = $clients->count();

        if ($total === 0) {
            return [
                'total_clients' => 0,
                'avg_score' => 0,
                'fully_complete' => 0,
                'pct_fully_complete' => 0,
                'missing_summary' => [],
            ];
        }

        $scores = [];
        $missingCounts = [];
        $fullyComplete = 0;

        foreach ($clients as $client) {
            $result = $this->clientCompletenessScore($client);
            $scores[] = $result['score'];
            if ($result['score'] === 100) {
                $fullyComplete++;
            }
            foreach ($result['missing'] as $field) {
                $missingCounts[$field] = ($missingCounts[$field] ?? 0) + 1;
            }
        }

        arsort($missingCounts);

        return [
            'total_clients' => $total,
            'avg_score' => (int) round(array_sum($scores) / $total),
            'fully_complete' => $fullyComplete,
            'pct_fully_complete' => (int) round(($fullyComplete / $total) * 100),
            'missing_summary' => $missingCounts,
        ];
    }

    /**
     * Find potential duplicate clients.
     *
     * Returns groups of clients that share SSN last four + birth year,
     * or exact first name + last name + birth year.
     */
    public function duplicateDetection(): Collection
    {
        $duplicates = collect();

        // SSN last four + birth year matches
        $ssnDups = DB::table('clients')
            ->select('ssn_last_four', 'birth_year', DB::raw('COUNT(*) as cnt'), DB::raw('GROUP_CONCAT(id) as ids'))
            ->whereNotNull('ssn_last_four')
            ->whereNotNull('birth_year')
            ->whereNull('deleted_at')
            ->groupBy('ssn_last_four', 'birth_year')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($ssnDups as $row) {
            $clientIds = explode(',', $row->ids);
            $clients = Client::whereIn('id', $clientIds)->get();
            $duplicates->push([
                'match_type' => 'SSN Last 4 + Birth Year',
                'match_key' => "SSN: ***{$row->ssn_last_four}, Year: {$row->birth_year}",
                'clients' => $clients->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->fullName(),
                    'dob_year' => $c->birth_year,
                ])->toArray(),
            ]);
        }

        // Exact name + birth year matches
        $nameDups = DB::table('clients')
            ->select('first_name', 'last_name', 'birth_year', DB::raw('COUNT(*) as cnt'), DB::raw('GROUP_CONCAT(id) as ids'))
            ->whereNotNull('birth_year')
            ->whereNull('deleted_at')
            ->groupBy('first_name', 'last_name', 'birth_year')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($nameDups as $row) {
            $clientIds = explode(',', $row->ids);
            // Skip if already found via SSN
            if ($duplicates->contains(fn ($d) => count(array_intersect(array_column($d['clients'], 'id'), $clientIds)) > 1)) {
                continue;
            }
            $clients = Client::whereIn('id', $clientIds)->get();
            $duplicates->push([
                'match_type' => 'Name + Birth Year',
                'match_key' => "{$row->first_name} {$row->last_name}, Year: {$row->birth_year}",
                'clients' => $clients->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->fullName(),
                    'dob_year' => $c->birth_year,
                ])->toArray(),
            ]);
        }

        return $duplicates;
    }

    /**
     * Clients with lowest completeness scores.
     *
     * @return Collection<int, array{client: Client, score: int, missing: list<string>}>
     */
    public function leastCompleteClients(int $limit = 20): Collection
    {
        return Client::complete()
            ->get()
            ->map(fn (Client $c) => [
                'client' => $c,
                ...$this->clientCompletenessScore($c),
            ])
            ->sortBy('score')
            ->take($limit)
            ->values();
    }
}
