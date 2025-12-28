<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientRegistrationController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $personalToken = base64_encode(random_bytes(32));
        $apiToken = base64_encode(random_bytes(32));

        $client = Client::create([
            'name' => $validated['name'] ?? null,
            'api_token_hash' => Hash::make($apiToken),
            'encryption_key_encrypted' => Crypt::encryptString($personalToken),
        ]);

        return response()->json([
            'client_id' => $client->id,
            'personal_token' => $personalToken,
            'api_token' => $apiToken,
        ], 201);
    }
}
