<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('attaches documents to a client', function () {
    $client = Client::factory()->create();
    $document = ClientDocument::factory()->create([
        'client_id' => $client->id,
        'category' => 'income_verification',
        'original_name' => 'paystub.pdf',
    ]);

    expect($client->documents)->toHaveCount(1)
        ->and($client->documents->first()->original_name)->toBe('paystub.pdf')
        ->and($document->client->id)->toBe($client->id);
});

it('only allows admins to delete documents', function () {
    $document = ClientDocument::factory()->create();

    $admin = User::factory()->admin()->create();
    $supervisor = User::factory()->supervisor()->create();
    $caseworker = User::factory()->caseworker()->create();

    expect($admin->can('delete', $document))->toBeTrue()
        ->and($supervisor->can('delete', $document))->toBeFalse()
        ->and($caseworker->can('delete', $document))->toBeFalse()
        ->and($caseworker->can('create', ClientDocument::class))->toBeTrue()
        ->and($caseworker->can('view', $document))->toBeTrue();
});

it('removes the stored file when a document is force deleted', function () {
    Storage::fake('local');
    Storage::disk('local')->put('client-documents/test.pdf', 'pdf-content');

    $document = ClientDocument::factory()->create([
        'disk' => 'local',
        'path' => 'client-documents/test.pdf',
    ]);

    $document->forceDelete();

    Storage::disk('local')->assertMissing('client-documents/test.pdf');
});

it('keeps the stored file on soft delete', function () {
    Storage::fake('local');
    Storage::disk('local')->put('client-documents/keep.pdf', 'pdf-content');

    $document = ClientDocument::factory()->create([
        'disk' => 'local',
        'path' => 'client-documents/keep.pdf',
    ]);

    $document->delete();

    Storage::disk('local')->assertExists('client-documents/keep.pdf');
    expect(ClientDocument::withTrashed()->find($document->id)->trashed())->toBeTrue();
});
