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
python operator_cli.py --base-url http://localhost:8000 --operator-token changeme-operator
python operator_cli.py --base-url http://localhost:8000 --json
```

- Provide the operator token with `--operator-token` or via the `OPERATOR_TOKEN` (or legacy `OPERATOR_TOKENS`) environment variable.
- When `--json` is omitted, the script prints a simple table showing client id, name, status, and last-seen timestamp.
- Status is `online` when the server has seen the client within the past two minutes; otherwise it is `offline`.
