<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencySetting extends Model
{
    protected $fillable = [
        'agency_name',
        'agency_address_line_1',
        'agency_address_line_2',
        'agency_city',
        'agency_state',
        'agency_zip',
        'agency_county',
        'agency_phone',
        'agency_ein',
        'agency_website',
        'executive_director_name',
        'logo_path',
        'primary_color',
        'fiscal_year_start_month',
        'setup_completed',
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year_start_month' => 'integer',
            'setup_completed' => 'boolean',
            'mail_port' => 'integer',
            'mail_password' => 'encrypted',
        ];
    }

    /**
     * Get the singleton agency settings record, cached for the request lifecycle.
     */
    public static function current(): ?self
    {
        return once(fn () => static::first());
    }

    /**
     * Check if the initial setup wizard has been completed.
     */
    public static function isSetupComplete(): bool
    {
        $settings = static::current();

        return $settings !== null && $settings->setup_completed;
    }

    /**
     * Get the full formatted address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->agency_address_line_1,
            $this->agency_address_line_2,
        ]);

        $cityStateZip = array_filter([
            $this->agency_city,
            $this->agency_state ? "{$this->agency_state} {$this->agency_zip}" : $this->agency_zip,
        ]);

        $lines = [];
        if (! empty($parts)) {
            $lines[] = implode(', ', $parts);
        }
        if (! empty($cityStateZip)) {
            $lines[] = implode(', ', $cityStateZip);
        }

        return implode("\n", $lines);
    }
}
