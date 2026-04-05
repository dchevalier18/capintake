<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    /**
     * Fields that should never be logged in audit records.
     */
    protected static array $auditExcludedFields = [
        'ssn_encrypted',
        'password',
        'remember_token',
    ];

    /**
     * Fields whose values are encrypted at rest and should be masked in audit logs.
     * The field name will still appear (so auditors know it changed), but the
     * value is replaced with "[encrypted]" instead of showing raw ciphertext.
     */
    protected static array $auditEncryptedFields = [
        'date_of_birth',
        'amount',
        'annual_amount',
    ];

    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->recordAudit('created', null, $model->getAuditableAttributes());
        });

        static::updated(function ($model) {
            $changed = $model->getDirty();
            $original = collect($model->getOriginal())
                ->only(array_keys($changed))
                ->toArray();

            $filtered = $model->filterAuditFields($changed);
            $filteredOriginal = $model->filterAuditFields($original);

            if (!empty($filtered)) {
                $model->recordAudit('updated', $filteredOriginal, $filtered);
            }
        });

        static::deleted(function ($model) {
            $model->recordAudit('deleted', $model->getAuditableAttributes(), null);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->recordAudit('restored', null, $model->getAuditableAttributes());
            });
        }
    }

    protected function recordAudit(string $action, ?array $oldValues, ?array $newValues): void
    {
        try {
            $ipAddress = null;
            try {
                $ipAddress = request()->ip();
            } catch (\Throwable) {
                // CLI context — no request available
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'auditable_type' => $this->getMorphClass(),
                'auditable_id' => $this->getKey(),
                'action' => $action,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $ipAddress,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Don't let audit logging failures break the application
        }
    }

    protected function getAuditableAttributes(): array
    {
        return $this->filterAuditFields($this->attributesToArray());
    }

    protected function filterAuditFields(array $attributes): array
    {
        return collect($attributes)
            ->except(static::$auditExcludedFields)
            ->map(function ($value, $key) {
                if (in_array($key, static::$auditEncryptedFields, true) && $value !== null) {
                    return '[encrypted]';
                }

                return $value;
            })
            ->toArray();
    }
}
