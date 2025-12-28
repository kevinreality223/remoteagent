# Python reference client

This client exercises the Laravel short-poll messaging API. It can register a client, poll with the required 3s→30s exponential backoff, decrypt messages, acknowledge cursors, and send encrypted payloads back to the server.

## Setup
```bash
cd client
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
```

## Usage
```
python client.py --base-url http://localhost:8000 --creds ./credentials.json --register
python client.py --base-url http://localhost:8000 --creds ./credentials.json --poll
python client.py --base-url http://localhost:8000 --creds ./credentials.json --send "Hello from Python" --send-type event
```

- `--register` creates a new client using the API and saves `client_id`, `personal_token`, and `api_token` to the credentials file.
- `--poll` starts the short-poll loop. It decrypts messages with AES-256-GCM, prints the plaintext payload, and POSTs an ack with the latest message id. Poll intervals grow by +3s up to 30s on empty responses and reset to 3s after receiving a message.
- `--send` encrypts the provided plaintext payload and POSTs it to `/api/v1/messages/send`. You can also include recipients with `--to-client-id uuid --to-client-id uuid` to request server fan-out.

The script reconstructs the server's associated data (AAD) for decryption using the message creation timestamp and client id, matching the server's AES-GCM usage.
