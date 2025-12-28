<?php
// Minimal CLI simulator for polling with backoff
$baseUrl = getenv('API_URL') ?: 'http://localhost/api/v1';
$clientId = getenv('CLIENT_ID');
$apiToken = getenv('API_TOKEN');
$encKey = base64_decode(getenv('PERSONAL_TOKEN'));

if (! $clientId || ! $apiToken || ! $encKey) {
    fwrite(STDERR, "Missing CLIENT_ID/API_TOKEN/PERSONAL_TOKEN env vars\n");
    exit(1);
}

$delay = 3;
$cursor = null;
while (true) {
    $query = $cursor ? "?cursor=" . urlencode($cursor) : '';
    $opts = [
        'http' => [
            'header' => [
                'Authorization: Bearer ' . $apiToken,
                'X-Client-Id: ' . $clientId,
            ],
            'ignore_errors' => true,
        ],
    ];
    $resp = @file_get_contents("{$baseUrl}/messages/poll{$query}", false, stream_context_create($opts));
    $code = 0;
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('#^HTTP/\S+\s(\d{3})#', $header, $m)) {
                $code = (int) $m[1];
                break;
            }
        }
    }

    if ($code === 200 && $resp) {
        $data = json_decode($resp, true);
        foreach ($data['messages'] ?? [] as $message) {
            $cipher = base64_decode($message['ciphertext']);
            $nonce = base64_decode($message['nonce']);
            $tag = base64_decode($message['tag']);
            $plaintext = openssl_decrypt($cipher, 'aes-256-gcm', $encKey, OPENSSL_RAW_DATA, $nonce, $tag, json_encode(['client_id' => $clientId]));
            echo "Received: {$plaintext}\n";
            $cursor = $message['id'];
        }
        $delay = 3;
    } else {
        echo "No messages (status {$code})\n";
        $delay = min(30, $delay + 3);
    }
    sleep($delay);
}
