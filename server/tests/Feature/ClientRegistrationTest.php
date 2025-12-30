<?php

use App\Models\Client;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

test('it registers a client with a fingerprint', function () {
    $response = $this->postJson('/api/v1/clients/register', [
        'name' => 'python-client',
        'fingerprint' => 'fp-demo-123',
    ]);

    $response->assertCreated();
    $response->assertJsonStructure(['client_id', 'personal_token', 'api_token']);

    $this->assertDatabaseHas('clients', [
        'id' => $response['client_id'],
        'fingerprint' => 'fp-demo-123',
    ]);
});

test('register reuses existing credentials for the same fingerprint', function () {
    $first = $this->postJson('/api/v1/clients/register', [
        'fingerprint' => 'fp-demo-123',
    ])->assertCreated();

    $second = $this->postJson('/api/v1/clients/register', [
        'fingerprint' => 'fp-demo-123',
    ])->assertOk();

    expect($second['client_id'])->toBe($first['client_id']);
    expect($second['personal_token'])->toBe($first['personal_token']);
    expect($second['api_token'])->toBe($first['api_token']);

    $client = Client::where('fingerprint', 'fp-demo-123')->first();
    expect($client)->not()->toBeNull();
    expect(Crypt::decryptString($client->api_token_encrypted))->toBe($first['api_token']);
});

test('register refreshes credentials when stored secrets are not decryptable', function () {
    $client = Client::create([
        'fingerprint' => 'fp-demo-123',
        'api_token_hash' => Hash::make('bad-token'),
        'api_token_encrypted' => 'plaintext-token',
        'encryption_key_encrypted' => 'plaintext-key',
    ]);

    $response = $this->postJson('/api/v1/clients/register', [
        'fingerprint' => 'fp-demo-123',
        'name' => 'python-client',
    ])->assertOk();

    expect($response['client_id'])->toBe($client->id);
    expect($response['api_token'])->not()->toBe('bad-token');

    $client->refresh();
    expect(Crypt::decryptString($client->api_token_encrypted))->toBe($response['api_token']);
    expect(Crypt::decryptString($client->encryption_key_encrypted))->toBe($response['personal_token']);
    expect($client->name)->toBe('python-client');
});
