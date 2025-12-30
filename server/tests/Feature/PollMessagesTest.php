<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Message;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class PollMessagesTest extends TestCase
{
    public function test_poll_returns_encrypted_payload_only(): void
    {
        $client = Client::factory()->create();
        $apiToken = 'token';
        $client->api_token_hash = Hash::make($apiToken);
        $client->save();

        Message::create([
            'from_client_id' => null,
            'to_client_id' => $client->id,
            'type' => 'event',
            'ciphertext' => base64_encode('cipher'),
            'nonce' => base64_encode('nonce'),
            'tag' => base64_encode('tag'),
            'created_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$apiToken,
            'X-Client-Id' => $client->id,
        ])->getJson('/api/v1/messages/poll');

        $response->assertOk();
        $response->assertJsonMissingPath('messages.0.payload');
    }

    public function test_poll_updates_last_seen_timestamp(): void
    {
        $now = Date::create(2024, 1, 1, 12, 0, 0);
        Date::setTestNow($now);

        $client = Client::factory()->create(['last_seen_at' => $now->copy()->subMinutes(10)]);
        $apiToken = 'token';
        $client->api_token_hash = Hash::make($apiToken);
        $client->save();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$apiToken,
            'X-Client-Id' => $client->id,
        ])->getJson('/api/v1/messages/poll')->assertNoContent();

        $client->refresh();
        $this->assertTrue($client->last_seen_at->equalTo($now));

        Date::setTestNow();
    }
}
