<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class AuditLogViewer extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $title = 'Audit Log';

    protected string $view = 'filament.pages.audit-log-viewer';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 11;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->role === UserRole::Admin;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AuditLog::query()->latest('created_at'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->default('System')
                    ->sortable(),

                TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('auditable_type')
                    ->label('Model')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->sortable(),

                TextColumn::make('auditable_id')
                    ->label('Record ID')
                    ->sortable(),

                TextColumn::make('summary')
                    ->label('Changes')
                    ->state(function (AuditLog $record): string {
                        if ($record->action === 'created') {
                            return 'New record created';
                        }
                        if ($record->action === 'deleted') {
                            return 'Record deleted';
                        }
                        if ($record->action === 'restored') {
                            return 'Record restored';
                        }
                        if (is_array($record->new_values)) {
                            $changes = [];
                            $oldValues = $record->old_values ?? [];
                            foreach ($record->new_values as $field => $newVal) {
                                if ($field === 'updated_at') {
                                    continue;
                                }
                                $oldVal = $oldValues[$field] ?? null;
                                $old = $this->formatAuditValue($oldVal);
                                $new = $this->formatAuditValue($newVal);
                                $changes[] = "{$field}: {$old} → {$new}";
                            }

                            return implode(', ', array_slice($changes, 0, 3))
                                . (count($changes) > 3 ? ' +' . (count($changes) - 3) . ' more' : '');
                        }

                        return '';
                    })
                    ->wrap(),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('auditable_type')
                    ->label('Model')
                    ->options([
                        'App\\Models\\Client' => 'Client',
                        'App\\Models\\Household' => 'Household',
                        'App\\Models\\HouseholdMember' => 'Household Member',
                        'App\\Models\\Enrollment' => 'Enrollment',
                        'App\\Models\\ServiceRecord' => 'Service Record',
                        'App\\Models\\IncomeRecord' => 'Income Record',
                        'App\\Models\\User' => 'User',
                    ]),

                SelectFilter::make('user_id')
                    ->label('User')
                    ->options(fn () => User::pluck('name', 'id')->toArray()),

                SelectFilter::make('action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                    ]),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()
                    ->modalHeading(fn (AuditLog $record): string => class_basename($record->auditable_type) . " #{$record->auditable_id} — " . ucfirst($record->action))
                    ->infolist(function (AuditLog $record): array {
                        $entries = [
                            TextEntry::make('created_at')
                                ->label('Date/Time')
                                ->dateTime('M j, Y g:i:s A'),
                            TextEntry::make('user.name')
                                ->label('User')
                                ->default('System'),
                            TextEntry::make('action')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'created' => 'success',
                                    'updated' => 'warning',
                                    'deleted' => 'danger',
                                    'restored' => 'info',
                                    default => 'gray',
                                }),
                            TextEntry::make('ip_address')
                                ->label('IP Address')
                                ->default('N/A'),
                        ];

                        if ($record->old_values) {
                            $entries[] = TextEntry::make('old_values_display')
                                ->label('Previous Values')
                                ->state(fn () => collect($record->old_values)
                                    ->map(fn ($value, $key) => "{$key}: " . $this->formatAuditValue($value))
                                    ->implode("\n"))
                                ->markdown();
                        }

                        if ($record->new_values) {
                            $entries[] = TextEntry::make('new_values_display')
                                ->label('New Values')
                                ->state(fn () => collect($record->new_values)
                                    ->map(fn ($value, $key) => "{$key}: " . $this->formatAuditValue($value))
                                    ->implode("\n"))
                                ->markdown();
                        }

                        return $entries;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    /**
     * Format an audit log value for display.
     * Detects encrypted blobs (base64-encoded Laravel encrypted strings)
     * and replaces them with a readable placeholder.
     */
    protected function formatAuditValue(mixed $value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        $str = (string) $value;

        // Detect Laravel encrypted values (base64-encoded JSON with iv/value/mac)
        if ($str === '[encrypted]' || (strlen($str) > 100 && str_starts_with($str, 'eyJ'))) {
            return '[encrypted]';
        }

        return $str;
    }
}
