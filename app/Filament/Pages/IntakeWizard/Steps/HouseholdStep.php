<?php

declare(strict_types=1);

namespace App\Filament\Pages\IntakeWizard\Steps;

use App\Filament\Pages\IntakeWizard;
use App\Models\Household;
use App\Services\Lookup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\HtmlString;

class HouseholdStep
{
    public static function make(IntakeWizard $page): Step
    {
        return Step::make('Household')
            ->icon('heroicon-o-home')
            ->description('Household details and members')
            ->schema([
                Section::make('Household')
                    ->schema([
                        Select::make('household_mode')
                            ->label('Household Assignment')
                            ->options([
                                'new' => 'Create new household (using address from Step 1)',
                                'existing' => 'Link to an existing household',
                            ])
                            ->default('new')
                            ->required()
                            ->live(),

                        Select::make('existing_household_id')
                            ->label('Search Existing Households')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Household::query()
                                ->where('address_line_1', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%")
                                ->orWhere('zip', 'like', "%{$search}%")
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn (Household $h): array => [$h->id => $h->fullAddress()])
                                ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): string => Household::find($value)?->fullAddress() ?? '')
                            ->visible(fn (Get $get): bool => $get('household_mode') === 'existing')
                            ->required(fn (Get $get): bool => $get('household_mode') === 'existing'),

                        Placeholder::make('new_household_address')
                            ->label('Household Address')
                            ->content(function () use ($page): HtmlString {
                                $d = $page->data;
                                $addr = implode(', ', array_filter([
                                    $d['address_line_1'] ?? '',
                                    $d['address_line_2'] ?? '',
                                    $d['city'] ?? '',
                                    ($d['state'] ?? '').' '.($d['zip'] ?? ''),
                                ]));

                                return new HtmlString('<span class="text-sm">'.e($addr).'</span>');
                            })
                            ->visible(fn (Get $get): bool => $get('household_mode') === 'new'),

                        Select::make('housing_type')
                            ->options(fn () => Lookup::options('housing_type'))
                            ->default('rent'),

                        Select::make('household_type')
                            ->label('Household Type')
                            ->options(fn () => Lookup::options('household_type')),

                        Toggle::make('is_head_of_household')
                            ->label('This client is the head of household')
                            ->default(true)
                            ->live(),

                        TextInput::make('relationship_to_head')
                            ->label('Relationship to Head of Household')
                            ->maxLength(100)
                            ->visible(fn (Get $get): bool => ! $get('is_head_of_household')),
                    ])
                    ->columns(2),

                Section::make('Household Members')
                    ->description('Add other people living in the household')
                    ->schema([
                        Repeater::make('household_members')
                            ->label('')
                            ->schema([
                                TextInput::make('first_name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('last_name')
                                    ->required()
                                    ->maxLength(255),

                                DatePicker::make('date_of_birth')
                                    ->maxDate(now()),

                                Select::make('relationship_to_client')
                                    ->label('Relationship')
                                    ->options(fn () => Lookup::options('relationship_to_head'))
                                    ->required(),

                                Select::make('gender')
                                    ->options(fn () => Lookup::options('gender')),

                                Select::make('employment_status')
                                    ->label('Employment')
                                    ->options(fn () => Lookup::options('employment_status')),

                                Select::make('race')
                                    ->options(fn () => Lookup::options('race')),

                                Select::make('ethnicity')
                                    ->options(fn () => Lookup::options('ethnicity')),

                                Select::make('education_level')
                                    ->label('Education')
                                    ->options(fn () => Lookup::options('education_level')),

                                Toggle::make('is_disabled')
                                    ->label('Disabling Condition')
                                    ->default(false)
                                    ->inline(false),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => trim(($state['first_name'] ?? '').' '.($state['last_name'] ?? '')) ?: null)
                            ->addActionLabel('Add household member')
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->live(),

                        Placeholder::make('household_size_display')
                            ->label('Total Household Size')
                            ->content(function (Get $get): HtmlString {
                                $members = $get('household_members') ?? [];
                                $size = count($members) + 1;

                                return new HtmlString(
                                    '<span class="text-lg font-bold">'.$size.'</span>'
                                    .'<span class="text-sm text-gray-500 ml-2">(client + '.count($members).' member'.(count($members) !== 1 ? 's' : '').')</span>'
                                );
                            }),
                    ]),
            ])
            ->afterValidation(function () use ($page): void {
                $page->saveDraftStep2();
            });
    }
}
