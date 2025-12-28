<?php

namespace App\Http\Controllers;

use App\Jobs\MessageFanoutJob;
use App\Models\Message;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;

class ClientMessageController extends Controller
{
    public function send(Request $request, EncryptionService $encryption)
    {
        $client = $request->attributes->get('client');
        $validated = $request->validate([
            'ciphertext' => ['required', 'string'],
            'nonce' => ['required', 'string'],
            'tag' => ['required', 'string'],
            'aad' => ['sometimes', 'array'],
        ]);

        $key = Crypt::decryptString($client->encryption_key_encrypted);
        $aad = $validated['aad'] ?? [];
        $payload = $encryption->decrypt($key, $validated['ciphertext'], $validated['nonce'], $validated['tag'], $aad);

        $timestamp = Date::now();
        $store = $encryption->encrypt($key, $payload, [
            'from' => $client->id,
            'ts' => $timestamp->toIso8601String(),
        ]);

        Message::create([
            'from_client_id' => $client->id,
            'to_client_id' => $client->id,
            'type' => $payload['type'] ?? 'client',
            'ciphertext' => $store['ciphertext'],
            'nonce' => $store['nonce'],
            'tag' => $store['tag'],
            'created_at' => $timestamp,
        ]);

        if (isset($payload['to_client_ids']) && is_array($payload['to_client_ids'])) {
            MessageFanoutJob::dispatch($payload['to_client_ids'], $client->id, $payload['type'] ?? 'client', $payload['payload'] ?? []);
        }

        return response()->json(['status' => 'accepted']);
    }
}
