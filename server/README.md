# Laravel 11 Short-Poll Live Messaging

Production-ready short-poll messaging reference built on Laravel 11 / PHP 8.2. Clients poll for encrypted messages with exponential backoff; the server responds immediately (no long polling, SSE, or WebSockets). This folder contains the server-side API; see `../client` for a Python reference client.

## Features
- Client registration with per-client API token and per-client encryption key.
- Application-layer AEAD encryption (AES-256-GCM) for all message bodies; plaintext is never stored.
- Short-poll GET endpoint that immediately returns messages (or 204 when empty) with rate limiting and per-client cursors.
- Ack endpoint to advance delivery cursor.
- Admin/internal publish endpoint with queued fan-out to many clients.
- Client-to-server encrypted send endpoint.
- Cleanup-ready data model with message receipts for durable cursors.
- Docker Compose for MySQL + Redis + app.
- PHP CLI simulator implementing the client exponential-backoff poller.
- Browser client (`public/client.html`) showing backoff polling, AES-GCM encryption/decryption, and ack flows.

## API
Base path: `/api/v1`

### Register client
`POST /api/v1/clients/register`
  - Body: `{ "name": "optional", "fingerprint": "machine-unique" }`
  - Response: `{ client_id, personal_token, api_token }`
  - Registrations are idempotent per `fingerprint`; re-registering the same fingerprint returns the same credentials.
  - The `personal_token` is the symmetric encryption key (base64). The `api_token` is the bearer token for authenticating requests.

### Publish message (admin)
`POST /api/v1/messages/publish`
- Headers: `X-Admin-Token: <ADMIN_TOKEN env value>`
- Body: `{ "to_client_ids": ["uuid"], "type": "event", "payload": { ...plaintext... } }`
- The server encrypts payloads per client and enqueues persistence via Redis/queue.

### Client -> server send
`POST /api/v1/messages/send`
- Headers: `Authorization: Bearer <api_token>`, `X-Client-Id: <client_id>`
- Body: `{ "ciphertext": "...", "nonce": "...", "tag": "...", "aad": { ... } }`
- Server decrypts using the stored personal_token and re-encrypts at rest.

### Poll messages (short poll)
`GET /api/v1/messages/poll?cursor=<last_id>`
- Headers: `Authorization: Bearer <api_token>`, `X-Client-Id: <client_id>`
- Returns `204` when no messages, otherwise `{ "messages": [{ id, type, ciphertext, nonce, tag, created_at }] }`.
- Messages are encrypted per client; plaintext is never returned.

### Ack messages
`POST /api/v1/messages/ack`
- Headers: `Authorization: Bearer <api_token>`, `X-Client-Id: <client_id>`
- Body: `{ "last_received_id": <message id> }`

### Operator: list clients
`GET /api/v1/operators/clients`
- Headers: `X-Operator-Token: <one of OPERATOR_TOKENS>`
- Response: `{ clients: [{ id, name, created_at, last_seen_at, status }] }`
- `status` is `online` when the client has contacted the server in the last two minutes; otherwise it is `offline`.

## Encryption format
- Algorithm: AES-256-GCM with a per-client 32-byte key (base64 encoded in `personal_token`; clients must base64-decode to raw bytes before use).
- Fields: `ciphertext`, `nonce` (base64 96-bit), `tag` (base64 128-bit), optional `aad` JSON.
- AAD includes delivery metadata (client id, timestamp). Ciphertext holds JSON payload including message type and payload.
- Each message uses a new random nonce.

## Client polling backoff
- Start interval: 3s
- If poll returns 204, add +3s until max 30s (3, 6, 9 ... 30)
- Upon receiving at least one message, reset next poll to 3s
- Server always responds immediately; no held connections.

## Scaling for 1k+ clients
- Indexed queries on `(to_client_id, id)` keep polls fast.
- Per-client rate limits via Laravel rate limiter (`poll` limiter at 120 req/min/client).
- Redis queue for fan-out and async sends.
- Horizontal scale with stateless app servers behind a load balancer; share DB + Redis.
- Messages table can be partitioned/archived; periodic cleanup of acked messages is recommended.

## Running locally
```bash
cd server
cp .env.example .env
# set APP_KEY, ADMIN_TOKEN (defaults use sqlite)
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

The default `.env.example` uses sqlite, file-based caching, and the synchronous queue driver so you can run the API without Redis. If you do want Redis-backed rate limiting/queues, switch `CACHE_DRIVER`/`QUEUE_CONNECTION` to `redis` before starting the app.

### Docker
```
cd server
docker-compose up -d --build
# artisan commands run inside app container
```

> Note: The default `.env.example` now points to sqlite so you can run the API without a separate MySQL instance. If you want to
> use MySQL/Redis (as in Docker), update `DB_CONNECTION`, `DB_HOST`, and credentials accordingly before running migrations.

## Client simulators
- **Python client**: see `../client` for a Requests + cryptography implementation that registers, polls with the 3s→30s backoff, decrypts messages, acks cursors, and can send encrypted payloads.
- **Operator CLI**: see `../operator` for a Python script that lists registered clients and shows their online/offline status using operator tokens.
- **PHP CLI**: demonstrates registration, exponential-backoff polling, decrypting messages, and sending encrypted content. See `scripts/client_simulator.php`.
- **Browser client**: open `public/client.html` in the running app. It supports registering or pasting existing credentials, polls with the required 3s→30s backoff, decrypts per-client messages, auto-acks the latest cursor, and encrypts outbound messages using AES-GCM via Web Crypto.
