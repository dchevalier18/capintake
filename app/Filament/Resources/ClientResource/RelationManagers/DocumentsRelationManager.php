<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Models\ClientDocument;
use App\Services\Lookup;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('path')
                    ->label('File')
                    ->disk('local')
                    ->directory('client-documents')
                    ->visibility('private')
                    ->maxSize(10240) // 10 MB
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->required()
                    ->storeFileNamesIn('original_name')
                    ->columnSpanFull(),

                Select::make('category')
                    ->label('Document Type')
                    ->options(fn () => Lookup::options('document_type')),

                Textarea::make('notes')
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('original_name')
                    ->label('File')
                    ->searchable(),

                TextColumn::make('category')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? Lookup::label('document_type', $state) ?? ucfirst(str_replace('_', ' ', $state)) : '—'),

                TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024, 0).' KB' : '—'),

                TextColumn::make('uploader.name')
                    ->label('Uploaded By'),

                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('m/d/Y g:i A')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload Document')
                    ->mutateDataUsing(function (array $data): array {
                        $data['uploaded_by'] = Auth::id();
                        $data['disk'] = 'local';

                        if (! empty($data['path']) && Storage::disk('local')->exists($data['path'])) {
                            $data['size'] = Storage::disk('local')->size($data['path']);
                            $data['mime_type'] = Storage::disk('local')->mimeType($data['path']);
                        }

                        return $data;
                    }),
            ])
            ->actions([
                Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (ClientDocument $record): ?StreamedResponse {
                        if (! Auth::user()?->can('view', $record)) {
                            abort(403);
                        }

                        if (! Storage::disk($record->disk)->exists($record->path)) {
                            Notification::make()
                                ->danger()
                                ->title('File missing from storage')
                                ->send();

                            return null;
                        }

                        return Storage::disk($record->disk)->download($record->path, $record->original_name);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
