<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

class EncryptedDate implements CastsAttributes
{
    /**
     * Decrypt the value and return as a Carbon date.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($value);

            return Carbon::parse($decrypted)->startOfDay();
        } catch (\Throwable) {
            // Fallback for unencrypted values (e.g. during migration)
            return Carbon::parse($value)->startOfDay();
        }
    }

    /**
     * Encrypt the date value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            $dateString = $value->format('Y-m-d');
        } else {
            $dateString = Carbon::parse($value)->format('Y-m-d');
        }

        return Crypt::encryptString($dateString);
    }
}
