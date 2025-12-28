<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class EncryptionService
{
    public function encryptForClient(Client $client, array $plaintext, array $aad = []): array
    {
        $key = $this->getClientKey($client);
        $iv = random_bytes(12);
        $payload = json_encode($plaintext);
        $aadString = json_encode($aad);
        $cipher = openssl_encrypt($payload, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aadString);

        return [
            'ciphertext' => base64_encode($cipher),
            'nonce' => base64_encode($iv),
            'tag' => base64_encode($tag),
        ];
    }

    public function decryptFromClient(Client $client, string $ciphertext, string $nonce, string $tag, array $aad = []): ?array
    {
        $key = $this->getClientKey($client);
        $aadString = json_encode($aad);
        $plaintext = openssl_decrypt(base64_decode($ciphertext), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, base64_decode($nonce), base64_decode($tag), $aadString);

        return $plaintext ? json_decode($plaintext, true) : null;
    }

    public function getClientKey(Client $client): string
    {
        return Crypt::decryptString($client->encryption_key_encrypted);
    }
}
