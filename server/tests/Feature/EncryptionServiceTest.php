<?php

namespace Tests\Feature;

use App\Services\EncryptionService;
use PHPUnit\Framework\TestCase;

class EncryptionServiceTest extends TestCase
{
    public function test_round_trip(): void
    {
        $service = new EncryptionService();
        $key = base64_encode(random_bytes(32));
        $payload = ['hello' => 'world'];
        $aad = ['meta' => 'test'];

        $encrypted = $service->encrypt($key, $payload, $aad);
        $decrypted = $service->decrypt($key, $encrypted['ciphertext'], $encrypted['nonce'], $encrypted['tag'], $aad);

        $this->assertSame($payload, $decrypted);
    }
}
