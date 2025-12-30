import argparse
import json
import os
from typing import Dict, List

import requests


DEFAULT_OPERATOR_TOKEN = "changeme-operator"


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Operator CLI for the Laravel messaging server")
    parser.add_argument(
        "--base-url",
        default=os.getenv("OPERATOR_BASE_URL") or os.getenv("APP_URL") or "http://127.0.0.1:8000",
        help="Base URL of the API (default: OPERATOR_BASE_URL env or http://127.0.0.1:8000)",
    )
    parser.add_argument(
        "--operator-token",
        default=os.getenv("OPERATOR_TOKEN") or os.getenv("OPERATOR_TOKENS") or DEFAULT_OPERATOR_TOKEN,
        help=f"Operator token (default: {DEFAULT_OPERATOR_TOKEN} or OPERATOR_TOKEN env var)",
    )
    parser.add_argument("--json", action="store_true", help="Output raw JSON instead of a table")
    return parser.parse_args()


def fetch_clients(base_url: str, token: str) -> List[Dict[str, str]]:
    response = requests.get(
        f"{base_url.rstrip('/')}/api/v1/operators/clients",
        headers={"X-Operator-Token": token},
        timeout=15,
    )
    response.raise_for_status()
    body = response.json()
    return body.get("clients", [])


def print_table(clients: List[Dict[str, str]]) -> None:
    if not clients:
        print("No clients registered.")
        return

    header = f"{'Client ID':36}  {'Name':20}  {'Status':7}  Last seen"
    print(header)
    print("-" * len(header))

    for client in clients:
        name = (client.get("name") or "-")
        if len(name) > 20:
            name = name[:17] + "..."
        last_seen = client.get("last_seen_at") or "never"
        print(f"{client.get('id',''):36}  {name:20}  {client.get('status','?'):7}  {last_seen}")


def main() -> None:
    args = parse_args()
    if not args.operator_token:
        raise SystemExit("Missing operator token. Provide --operator-token or set OPERATOR_TOKEN.")

    clients = fetch_clients(args.base_url, args.operator_token)

    if args.json:
        print(json.dumps(clients, indent=2))
    else:
        print_table(clients)


if __name__ == "__main__":
    main()
