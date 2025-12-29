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
            'fingerprint' => ['required', 'string', 'max:255'],
        ]);

        $existing = Client::where('fingerprint', $validated['fingerprint'])->first();
        if ($existing) {
            return response()->json([
                'client_id' => $existing->id,
                'personal_token' => Crypt::decryptString($existing->encryption_key_encrypted),
                'api_token' => Crypt::decryptString($existing->api_token_encrypted),
            ]);
        }

        $personalToken = base64_encode(random_bytes(32));
        $apiToken = base64_encode(random_bytes(32));

        $client = Client::create([
            'name' => $validated['name'] ?? null,
            'fingerprint' => $validated['fingerprint'],
            'api_token_hash' => Hash::make($apiToken),
            'api_token_encrypted' => Crypt::encryptString($apiToken),
            'encryption_key_encrypted' => Crypt::encryptString($personalToken),
        ]);

        return response()->json([
            'client_id' => $client->id,
            'personal_token' => $personalToken,
            'api_token' => $apiToken,
        ], 201);
    }
}
