<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $personalToken = base64_encode(random_bytes(32));
        $apiToken = base64_encode(random_bytes(32));

        return [
            'name' => $this->faker->name(),
            'api_token_hash' => Hash::make($apiToken),
            'encryption_key_encrypted' => Crypt::encryptString($personalToken),
        ];
    }
}
