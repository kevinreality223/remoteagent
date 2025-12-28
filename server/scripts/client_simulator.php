<?php
// Simple PHP CLI simulator for the short-poll client.

$baseUrl = getenv('API_BASE') ?: 'http://localhost:8000';

function request(string $method, string $url, array $body = null, array $headers = [])
{
    $opts = [
        'http' => [
            'method' => $method,
            'header' => array_map(fn($k, $v) => "$k: $v", array_keys($headers), $headers),
            'ignore_errors' => true,
        ],
    ];
    if ($body !== null) {
        $opts['http']['content'] = json_encode($body);
        $opts['http']['header'][] = 'Content-Type: application/json';
    }
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? 'HTTP/1.1 500';
    preg_match('#\s(\d{3})#', $statusLine, $m);
    $status = (int)($m[1] ?? 500);
    return [$status, $response ? json_decode($response, true) : null];
}

[$status, $register] = request('POST', "$baseUrl/api/v1/clients/register");
if ($status !== 201) {
    fwrite(STDERR, "Failed to register client\n");
    exit(1);
}

$clientId = $register['client_id'];
$apiToken = $register['api_token'];
$personalToken = $register['personal_token'];

echo "Registered client: $clientId\n";

function aes_encrypt(string $key, array $payload, array $aad = []): array
{
    $iv = random_bytes(12);
    $tag = '';
    $aadJson = json_encode($aad);
    $cipher = openssl_encrypt(json_encode($payload), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aadJson, 16);
    return [
        'ciphertext' => base64_encode($cipher),
        'nonce' => base64_encode($iv),
        'tag' => base64_encode($tag),
        'aad' => $aad,
    ];
}

function aes_decrypt(string $key, array $data, array $aad = []): array
{
    $aadJson = json_encode($aad);
    $plaintext = openssl_decrypt(base64_decode($data['ciphertext']), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, base64_decode($data['nonce']), base64_decode($data['tag']), $aadJson);
    return json_decode($plaintext, true);
}

$interval = 3;
$cursor = null;

while (true) {
    [$status, $body] = request('GET', "$baseUrl/api/v1/messages/poll" . ($cursor ? "?cursor=$cursor" : ''), null, [
        'Authorization' => 'Bearer '.$apiToken,
        'X-Client-Id' => $clientId,
    ]);

    if ($status === 200 && isset($body['messages'])) {
        echo "Received " . count($body['messages']) . " message(s)\n";
        foreach ($body['messages'] as $msg) {
            $cursor = $msg['id'];
            $plaintext = aes_decrypt($personalToken, $msg);
            echo "Message {$msg['id']} type {$msg['type']} -> " . json_encode($plaintext) . "\n";
        }
        $interval = 3;
        request('POST', "$baseUrl/api/v1/messages/ack", ['last_received_id' => $cursor], [
            'Authorization' => 'Bearer '.$apiToken,
            'X-Client-Id' => $clientId,
        ]);
    } else {
        echo "No messages (status $status).\n";
        $interval = min(30, $interval + 3);
    }

    if ($interval < 30) {
        $sendPayload = ['type' => 'ping', 'payload' => ['ts' => microtime(true)]];
        $encrypted = aes_encrypt($personalToken, $sendPayload);
        request('POST', "$baseUrl/api/v1/messages/send", $encrypted, [
            'Authorization' => 'Bearer '.$apiToken,
            'X-Client-Id' => $clientId,
        ]);
    }

    sleep($interval);
}
