<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\MessageReceipt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AckMessagesTest extends TestCase
{
    public function test_ack_updates_cursor(): void
    {
        $client = Client::factory()->create();
        $apiToken = 'token';
        $client->api_token_hash = Hash::make($apiToken);
        $client->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$apiToken,
            'X-Client-Id' => $client->id,
        ])->postJson('/api/v1/messages/ack', ['last_received_id' => 5]);

        $response->assertOk();
        $receipt = MessageReceipt::where('client_id', $client->id)->first();
        $this->assertNotNull($receipt);
        $this->assertEquals(5, $receipt->last_acked_message_id);
    }
}
