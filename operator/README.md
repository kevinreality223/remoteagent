# Operator CLI

Helper for operators to inspect registered clients via the Laravel messaging server's operator API.

## Setup
```bash
cd operator
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
```

## Usage
```
python operator_cli.py
```

- By default the CLI points at `http://127.0.0.1:8000`; override with the `OPERATOR_BASE_URL` environment variable or the `--base-url` flag.
- Provide the operator token with `--operator-token` or via the `OPERATOR_TOKEN` (or legacy `OPERATOR_TOKENS`) environment variable; when unset it defaults to `changeme-operator`.
- Provide the admin token with `--admin-token` or via `ADMIN_TOKEN`; when unset it defaults to `changeme-admin`.
- When `--json` is omitted, the script launches an interactive loop: it lists clients with numbers, lets you pick a client, prompts for a message type and JSON payload (plain text is wrapped as `{ "message": "..." }`), and queues the message for delivery. Press `r` to refresh or `q` to quit.
- Status is `online` when the server has seen the client within the past two minutes; otherwise it is `offline`.
