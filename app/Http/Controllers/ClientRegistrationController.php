<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientRegistrationController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $clientId = (string) Str::uuid();
        $apiToken = Str::random(64);
        $encryptionKey = random_bytes(32);

        $client = Client::create([
            'id' => $clientId,
            'name' => $request->input('name'),
            'api_token_hash' => Hash::make($apiToken),
            'encryption_key_encrypted' => Crypt::encryptString($encryptionKey),
        ]);

        return response()->json([
            'client_id' => $client->id,
            'personal_token' => base64_encode($encryptionKey),
            'api_token' => $apiToken,
        ], 201);
    }
}
