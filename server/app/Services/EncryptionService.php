<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Str;

class EncryptionService
{
    public function encrypt(string $key, array $payload, array $aad = []): array
    {
        $iv = random_bytes(12);
        $rawKey = base64_decode($key, true);
        if ($rawKey === false) {
            throw new \RuntimeException('Invalid encryption key encoding');
        }
        $plaintext = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $aadJson = json_encode($aad, JSON_UNESCAPED_SLASHES);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $rawKey, OPENSSL_RAW_DATA, $iv, $tag, $aadJson, 16);

        if ($cipher === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return [
            'ciphertext' => base64_encode($cipher),
            'nonce' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'aad' => $aad,
        ];
    }

    public function decrypt(string $key, string $ciphertext, string $nonce, string $tag, array $aad = []): array
    {
        $cipherRaw = base64_decode($ciphertext);
        $iv = base64_decode($nonce);
        $tagRaw = base64_decode($tag);
        $aadJson = json_encode($aad, JSON_UNESCAPED_SLASHES);
        $rawKey = base64_decode($key, true);
        if ($rawKey === false) {
            throw new DecryptException('Invalid encryption key encoding');
        }

        $plaintext = openssl_decrypt($cipherRaw, 'aes-256-gcm', $rawKey, OPENSSL_RAW_DATA, $iv, $tagRaw, $aadJson);
        if ($plaintext === false) {
            throw new DecryptException('Unable to decrypt payload');
        }

        return json_decode($plaintext, true) ?? [];
    }
}
