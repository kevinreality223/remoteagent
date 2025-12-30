<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class OperatorClientsTest extends TestCase
{
    public function test_operator_can_list_clients_with_status(): void
    {
        config(['operators.tokens' => 'secret-token']);

        $online = Client::factory()->create(['last_seen_at' => Date::now()]);
        $offline = Client::factory()->create(['last_seen_at' => Date::now()->subMinutes(5)]);

        $response = $this->withHeaders([
            'X-Operator-Token' => 'secret-token',
        ])->getJson('/api/v1/operators/clients');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $online->id, 'status' => 'online']);
        $response->assertJsonFragment(['id' => $offline->id, 'status' => 'offline']);
    }

    public function test_operator_token_is_required(): void
    {
        config(['operators.tokens' => 'secret-token']);

        Client::factory()->create();

        $response = $this->getJson('/api/v1/operators/clients');

        $response->assertUnauthorized();
    }

    public function test_operator_token_must_match_allowed_list(): void
    {
        config(['operators.tokens' => 'secret-token']);

        Client::factory()->create();

        $response = $this->withHeaders([
            'X-Operator-Token' => 'bad-token',
        ])->getJson('/api/v1/operators/clients');

        $response->assertForbidden();
    }
}
