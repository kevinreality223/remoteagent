# Laravel Short-Poll Encrypted Messaging

This repository contains a Laravel 11 application that implements short-polling live messaging with per-client end-to-end encryption (AES-256-GCM) and cursor-based delivery.

## Prerequisites
- PHP 8.2+ **with the OpenSSL extension enabled** (required for Composer TLS and AES-256-GCM encryption).
  - On Windows ensure `extension=openssl` is uncommented in `php.ini` and restart your shell.
  - If OpenSSL is unavailable, Composer can be run with `--no-plugins --no-scripts --no-dev --ignore-platform-req=ext-openssl --no-audit --no-progress --no-interaction --no-ansi --disable-tls`, but this is **not recommended** for production or development security.
- Composer
- MySQL (or PostgreSQL) and Redis (or use the bundled Docker Compose stack)

## Features
- Client registration issuing API token and encryption key.
- Application-layer encryption for all payloads (never store plaintext).
- Short-poll endpoint with immediate responses (no long-polling).
- Message acknowledgment to prevent loss on reconnect.
- Docker Compose stack with MySQL and Redis.
- Basic queue-ready fanout for multi-recipient publishing.
- Client simulator describing exponential backoff (3s -> 30s) polling.

## Running locally
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### Docker
```bash
docker-compose up -d --build
```
Services: `app` (Laravel), `mysql`, `redis`.

## API
- `POST /api/v1/clients/register` → `{client_id, personal_token (base64), api_token}`
- `POST /api/v1/messages/publish` → server encrypts and stores per-client messages.
- `POST /api/v1/messages/send` (Bearer + client_id) → server decrypts and stores.
- `GET /api/v1/messages/poll?cursor=<id>` (Bearer + client_id) → 200 with encrypted messages or 204.
- `POST /api/v1/messages/ack` (Bearer + client_id) → store last received id.

All authenticated calls require `Authorization: Bearer <api_token>` and `X-Client-Id` header or `client_id` field.

## Encryption format
- AES-256-GCM with random 96-bit nonce per message.
- Transport fields: `ciphertext`, `nonce`, `tag` (all base64 encoded).
- Associated data may include `client_id`, `timestamp`, and `message_id`.

## Polling backoff
Client polls every 3 seconds initially. When a poll returns 204 (no messages), the delay increases by 3 seconds until it reaches 30 seconds. Any poll that returns messages resets delay to 3 seconds.

## Scaling notes
- Index on `(to_client_id, id)` for fast polling.
- Per-client rate limiting via API token + middleware.
- Redis-backed queues for fanout to many clients.
- Stateless short-polling allows horizontal scaling behind a load balancer.

## Client simulator (PHP CLI sketch)
```
$clientId = '...';
$apiToken = '...';
$encKey = base64_decode('...');

$delay = 3;
while (true) {
    $resp = file_get_contents("http://localhost/api/v1/messages/poll?client_id=$clientId", false, stream_context_create([
        'http' => ['header' => "Authorization: Bearer $apiToken\r\n"]
    ]));
    // decrypt responses with AES-256-GCM using $encKey
    $delay = $resp ? 3 : min(30, $delay + 3);
    sleep($delay);
}
```
