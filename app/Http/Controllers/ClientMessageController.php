<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientMessageController extends Controller
{
    public function __construct(private readonly EncryptionService $encryptionService)
    {
    }

    public function send(Request $request)
    {
        $client = $request->attributes->get('auth_client');

        $data = $request->validate([
            'ciphertext' => ['required', 'string'],
            'nonce' => ['required', 'string'],
            'tag' => ['required', 'string'],
            'aad' => ['nullable', 'array'],
        ]);

        $aad = $data['aad'] ?? [];
        $aad['client_id'] = $client->id;
        $aad['timestamp'] = now()->toIso8601String();

        $plaintext = $this->encryptionService->decryptFromClient($client, $data['ciphertext'], $data['nonce'], $data['tag'], $aad);

        if (! $plaintext) {
            return response()->json(['message' => 'Unable to decrypt'], 422);
        }

        Message::create([
            'id' => Str::uuid(),
            'from_client_id' => $client->id,
            'to_client_id' => $client->id,
            'type' => $plaintext['type'] ?? 'client',
            'ciphertext' => $data['ciphertext'],
            'nonce' => $data['nonce'],
            'tag' => $data['tag'],
        ]);

        return response()->json(['status' => 'received']);
    }
}
