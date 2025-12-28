<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\Message;
use App\Services\EncryptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;

class MessageFanoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $clientIds,
        protected ?string $fromClientId,
        protected string $type,
        protected array $payload,
    ) {
    }

    public function handle(EncryptionService $encryption): void
    {
        foreach ($this->clientIds as $clientId) {
            $client = Client::find($clientId);
            if (!$client) {
                continue;
            }

            $key = Crypt::decryptString($client->encryption_key_encrypted);
            $timestamp = Date::now();
            $aad = [
                'to' => $client->id,
                'ts' => $timestamp->toIso8601String(),
            ];

            $encrypted = $encryption->encrypt($key, [
                'type' => $this->type,
                'payload' => $this->payload,
                'ts' => $timestamp->toIso8601String(),
            ], $aad);

            Message::create([
                'from_client_id' => $this->fromClientId,
                'to_client_id' => $client->id,
                'type' => $this->type,
                'ciphertext' => $encrypted['ciphertext'],
                'nonce' => $encrypted['nonce'],
                'tag' => $encrypted['tag'],
                'created_at' => $timestamp,
            ]);
        }
    }
}
