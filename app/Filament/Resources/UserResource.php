<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Users';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->minLength(10)
                            ->rule('regex:/[a-z]/')
                            ->rule('regex:/[A-Z]/')
                            ->rule('regex:/[0-9]/')
                            ->validationMessages([
                                'regex' => 'Password must contain uppercase, lowercase, and a number.',
                                'min' => 'Password must be at least 10 characters.',
                            ])
                            ->helperText('Min 10 characters, mixed case, at least one number.')
                            ->confirmed(),

                        TextInput::make('password_confirmation')
                            ->password()
                            ->revealable()
                            ->requiredWith('password')
                            ->dehydrated(false),

                        Select::make('role')
                            ->options(collect(UserRole::cases())->mapWithKeys(
                                fn (UserRole $role) => [$role->value => $role->label()]
                            ))
                            ->required()
                            ->default(UserRole::Caseworker->value),
                    ])
                    ->columns(2),

                Section::make('Profile')
                    ->schema([
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('title')
                            ->maxLength(255)
                            ->placeholder('e.g., Case Manager, Intake Specialist'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive users cannot log in.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label())
                    ->color(fn (UserRole $state): string => match ($state) {
                        UserRole::Admin => 'danger',
                        UserRole::Supervisor => 'warning',
                        UserRole::Caseworker => 'info',
                    })
                    ->sortable(),

                TextColumn::make('title')
                    ->searchable()
                    ->toggleable(),

                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(collect(UserRole::cases())->mapWithKeys(
                        fn (UserRole $role) => [$role->value => $role->label()]
                    )),

                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
