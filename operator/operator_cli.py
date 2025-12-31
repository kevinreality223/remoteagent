import argparse
import json
import os
from typing import Dict, List

import requests


DEFAULT_OPERATOR_TOKEN = "changeme-operator"
DEFAULT_ADMIN_TOKEN = "changeme-admin"


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
    parser.add_argument(
        "--admin-token",
        default=os.getenv("ADMIN_TOKEN") or DEFAULT_ADMIN_TOKEN,
        help=f"Admin token for publishing messages (default: {DEFAULT_ADMIN_TOKEN} or ADMIN_TOKEN env var)",
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

    header = f"{'#':3} {'Client ID':36}  {'Name':20}  {'Status':7}  Last seen"
    print(header)
    print("-" * len(header))

    for idx, client in enumerate(clients, start=1):
        name = (client.get("name") or "-")
        if len(name) > 20:
            name = name[:17] + "..."
        last_seen = client.get("last_seen_at") or "never"
        print(f"{idx:3} {client.get('id',''):36}  {name:20}  {client.get('status','?'):7}  {last_seen}")


def stream_client_messages(base_url: str, token: str, client_id: str, cursor: str | None) -> List[Dict]:
    params = {"cursor": cursor} if cursor else {}
    response = requests.get(
        f"{base_url.rstrip('/')}/api/v1/operators/clients/{client_id}/messages",
        headers={"X-Operator-Token": token},
        params=params,
        timeout=15,
    )
    if response.status_code == 204:
        return []
    response.raise_for_status()
    body = response.json()
    return body.get("messages", [])


def publish_message(base_url: str, admin_token: str, client_id: str, msg_type: str, payload: Dict) -> Dict:
    response = requests.post(
        f"{base_url.rstrip('/')}/api/v1/messages/publish",
        headers={"X-Admin-Token": admin_token, "Content-Type": "application/json"},
        json={"to_client_ids": [client_id], "type": msg_type, "payload": payload},
        timeout=15,
    )
    response.raise_for_status()
    return response.json()


def prompt_payload() -> Dict:
    print("Enter JSON payload for the message. If you provide plain text, it will be wrapped as {\"message\": <text>}.")
    raw = input("Payload: ").strip()
    if not raw:
        raise ValueError("Payload cannot be empty.")

    try:
        parsed = json.loads(raw)
        if not isinstance(parsed, dict):
            raise ValueError
        return parsed
    except ValueError:
        return {"message": raw}


def render_message_line(message: Dict) -> str:
    direction = "<-" if message.get("from_client_id") == message.get("to_client_id") else "->"
    payload = json.dumps(message.get("payload", {}))
    created_at = message.get("created_at") or "unknown"
    return (
        f"[{message.get('id')}] {created_at} {direction} {message.get('type')}: "
        f"{payload}"
    )


def live_conversation(base_url: str, operator_token: str, admin_token: str, client: Dict) -> None:
    print("\nEntering live view. Press Enter to poll, 's' to send a message, or 'q' to return.\n")
    cursor = None

    while True:
        try:
            messages = stream_client_messages(base_url, operator_token, client.get("id", ""), cursor)
        except requests.HTTPError as exc:
            print(f"Failed to read messages: {exc.response.text if exc.response else exc}\n")
            return
        except requests.RequestException as exc:
            print(f"Failed to read messages: {exc}\n")
            return

        if messages:
            for message in messages:
                cursor = message.get("id")
                print(render_message_line(message))
        else:
            print("(no new messages)")

        action = input("Action [poll/send/quit]: ").strip().lower()
        if action in {"q", "quit"}:
            print("")
            return
        if action in {"s", "send"}:
            msg_type = input("Message type [event]: ").strip() or "event"
            try:
                payload = prompt_payload()
            except ValueError as exc:
                print(f"{exc}\n")
                continue

            try:
                publish_message(base_url, admin_token, client.get("id", ""), msg_type, payload)
                print("Message queued successfully.\n")
            except requests.HTTPError as exc:
                print(f"Failed to send message: {exc.response.text if exc.response else exc}\n")
            except requests.RequestException as exc:
                print(f"Failed to send message: {exc}\n")


def interactive_loop(base_url: str, operator_token: str, admin_token: str) -> None:
    while True:
        clients = fetch_clients(base_url, operator_token)
        print_table(clients)

        if not clients:
            choice = input("No clients found. Press Enter to refresh or 'q' to quit: ").strip().lower()
            if choice in {"q", "quit"}:
                return
            continue

        selection = input("Select a client number to send a message, 'r' to refresh, or 'q' to quit: ").strip().lower()

        if selection in {"q", "quit"}:
            return
        if selection in {"r", "refresh", ""}:
            continue

        if not selection.isdigit():
            print("Invalid selection. Enter a client number, 'r' to refresh, or 'q' to quit.\n")
            continue

        index = int(selection) - 1
        if index < 0 or index >= len(clients):
            print("Selection out of range.\n")
            continue

        client = clients[index]
        print(f"\nSelected client: {client.get('name') or '-'} ({client.get('id')})")
        live_conversation(base_url, operator_token, admin_token, client)


def main() -> None:
    args = parse_args()
    if not args.operator_token:
        raise SystemExit("Missing operator token. Provide --operator-token or set OPERATOR_TOKEN.")

    if not args.admin_token:
        raise SystemExit("Missing admin token. Provide --admin-token or set ADMIN_TOKEN.")

    if args.json:
        clients = fetch_clients(args.base_url, args.operator_token)
        print(json.dumps(clients, indent=2))
    else:
        interactive_loop(args.base_url, args.operator_token, args.admin_token)


if __name__ == "__main__":
    main()
