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
1. Edit `conf.py` to set the server `BASE_URL` and optional `CLIENT_NAME`. Credentials default to `~/.remoteagent/client_credentials.json`.
2. Run the client with:

```bash
python -m client
```

The client will:

- Generate a machine fingerprint from hardware and OS hints.
- Register automatically using that fingerprint; if the server already knows the fingerprint it will reuse the same credentials instead of creating a duplicate record.
- Persist credentials to `CREDENTIALS_PATH` for subsequent launches.
- Enter the polling loop with the 3s→30s schedule, decrypt incoming messages, and acknowledge cursors.

Additional message handlers can be added in `handlers.py` to layer custom processing on top of the polling loop.
