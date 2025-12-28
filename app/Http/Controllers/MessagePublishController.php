<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Message;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class MessagePublishController extends Controller
{
    public function __construct(private readonly EncryptionService $encryptionService)
    {
    }

    public function publish(Request $request)
    {
        $data = $request->validate([
            'to_client_ids' => ['required', 'array'],
            'to_client_ids.*' => ['uuid'],
            'type' => ['required', 'string', 'max:100'],
            'payload' => ['required', 'array'],
        ]);

        $clients = Client::whereIn('id', $data['to_client_ids'])->get();

        foreach ($clients as $client) {
            $aad = ['client_id' => $client->id, 'timestamp' => now()->toIso8601String()];
            $encrypted = $this->encryptionService->encryptForClient($client, [
                'message_id' => (string) Str::uuid(),
                'type' => $data['type'],
                'payload' => $data['payload'],
            ], $aad);

            Message::create([
                'id' => Str::uuid(),
                'to_client_id' => $client->id,
                'type' => $data['type'],
                'ciphertext' => $encrypted['ciphertext'],
                'nonce' => $encrypted['nonce'],
                'tag' => $encrypted['tag'],
            ]);
        }

        return response()->json(['status' => 'queued'], 202);
    }
}
