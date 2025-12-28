<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFailureTest extends TestCase
{
    public function test_missing_token_fails(): void
    {
        $response = $this->getJson('/api/v1/messages/poll');
        $response->assertStatus(401);
    }

    public function test_invalid_token_fails(): void
    {
        $client = Client::factory()->create();
        $client->api_token_hash = Hash::make('expected');
        $client->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer wrong',
            'X-Client-Id' => $client->id,
        ])->getJson('/api/v1/messages/poll');

        $response->assertStatus(401);
    }
}
