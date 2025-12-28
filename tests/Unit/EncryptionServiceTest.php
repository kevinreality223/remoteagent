<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Services\EncryptionService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class EncryptionServiceTest extends TestCase
{
    public function test_encrypt_decrypt_roundtrip(): void
    {
        $client = Client::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test',
            'api_token_hash' => Hash::make('token'),
            'encryption_key_encrypted' => Crypt::encryptString(random_bytes(32)),
        ]);

        $service = new EncryptionService();
        $plaintext = ['hello' => 'world'];
        $aad = ['client_id' => $client->id];

        $encrypted = $service->encryptForClient($client, $plaintext, $aad);
        $decrypted = $service->decryptFromClient($client, $encrypted['ciphertext'], $encrypted['nonce'], $encrypted['tag'], $aad);

        $this->assertEquals($plaintext, $decrypted);
    }
}
