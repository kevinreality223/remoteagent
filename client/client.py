import argparse
import base64
import json
import secrets
import time
from collections import OrderedDict
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Dict, List, Optional

import requests
from cryptography.hazmat.primitives.ciphers.aead import AESGCM


def load_credentials(path: Path) -> Optional[Dict[str, str]]:
    if not path.exists():
        return None
    with path.open("r", encoding="utf-8") as fp:
        return json.load(fp)


def save_credentials(path: Path, data: Dict[str, str]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8") as fp:
        json.dump(data, fp, indent=2)


def auth_headers(creds: Dict[str, str]) -> Dict[str, str]:
    return {
        "Authorization": f"Bearer {creds['api_token']}",
        "X-Client-Id": creds["client_id"],
    }


def aes_key(key_b64: str) -> bytes:
    key = base64.b64decode(key_b64)
    if len(key) != 32:
        raise ValueError("personal_token must decode to 32 bytes (AES-256-GCM key)")
    return key


def encrypt_payload(key_b64: str, payload: Dict[str, Any], aad: Optional[Dict[str, Any]] = None) -> Dict[str, str]:
    key = aes_key(key_b64)
    iv = secrets.token_bytes(12)
    aad_bytes = json.dumps(aad or {}, separators=(",", ":"), ensure_ascii=False).encode()
    plaintext = json.dumps(payload, separators=(",", ":"), ensure_ascii=False).encode()
    cipher = AESGCM(key).encrypt(iv, plaintext, aad_bytes if aad else None)
    ciphertext, tag = cipher[:-16], cipher[-16:]
    return {
        "ciphertext": base64.b64encode(ciphertext).decode(),
        "nonce": base64.b64encode(iv).decode(),
        "tag": base64.b64encode(tag).decode(),
        "aad": aad or {},
    }


def decrypt_payload(key_b64: str, message: Dict[str, Any], aad: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
    key = aes_key(key_b64)
    ciphertext = base64.b64decode(message["ciphertext"])
    nonce = base64.b64decode(message["nonce"])
    tag = base64.b64decode(message["tag"])
    aad_bytes = json.dumps(aad or {}, separators=(",", ":"), ensure_ascii=False).encode()
    combined = ciphertext + tag
    plaintext = AESGCM(key).decrypt(nonce, combined, aad_bytes if aad else None)
    return json.loads(plaintext.decode())


def register_client(base_url: str, name: Optional[str], creds_path: Path) -> Dict[str, str]:
    response = requests.post(f"{base_url}/api/v1/clients/register", json={"name": name} if name else {})
    response.raise_for_status()
    data = response.json()
    save_credentials(creds_path, data)
    print(f"Registered client {data['client_id']} and saved credentials to {creds_path}")
    return data


def ack_messages(base_url: str, creds: Dict[str, str], last_id: str) -> None:
    response = requests.post(
        f"{base_url}/api/v1/messages/ack",
        headers=auth_headers(creds),
        json={"last_received_id": last_id},
        timeout=10,
    )
    response.raise_for_status()


def poll_once(base_url: str, creds: Dict[str, str], cursor: Optional[str]) -> List[Dict[str, Any]]:
    response = requests.get(
        f"{base_url}/api/v1/messages/poll",
        headers=auth_headers(creds),
        params={"cursor": cursor} if cursor else {},
        timeout=15,
    )
    if response.status_code == 204:
        return []
    response.raise_for_status()
    body = response.json()
    return body.get("messages", [])


def poll_loop(base_url: str, creds: Dict[str, str]) -> None:
    cursor: Optional[str] = None
    interval = 3
    while True:
        try:
            messages = poll_once(base_url, creds, cursor)
            if not messages:
                interval = min(interval + 3, 30)
                print(f"No messages. Next poll in {interval}s")
            else:
                for msg in messages:
                    aad = OrderedDict([("to", creds["client_id"]), ("ts", msg["created_at"])])
                    plaintext = decrypt_payload(creds["personal_token"], msg, aad)
                    print(f"[received #{msg['id']}] {json.dumps(plaintext)}")
                    cursor = msg["id"]
                ack_messages(base_url, creds, cursor)
                interval = 3
        except Exception as exc:  # pylint: disable=broad-except
            interval = min(interval + 3, 30)
            print(f"Error during poll: {exc}. Retrying in {interval}s")
        time.sleep(interval)


def send_message(base_url: str, creds: Dict[str, str], plaintext: str, send_type: str, recipients: List[str]) -> None:
    payload = {
        "type": send_type,
        "payload": {"message": plaintext},
    }
    if recipients:
        payload["to_client_ids"] = recipients
    timestamp = datetime.now(timezone.utc).isoformat()
    aad = OrderedDict([("from", creds["client_id"]), ("ts", timestamp)])
    encrypted = encrypt_payload(creds["personal_token"], payload, aad)
    response = requests.post(
        f"{base_url}/api/v1/messages/send",
        headers=auth_headers(creds),
        json={
            "ciphertext": encrypted["ciphertext"],
            "nonce": encrypted["nonce"],
            "tag": encrypted["tag"],
            "aad": encrypted["aad"],
        },
        timeout=15,
    )
    response.raise_for_status()
    print("Sent encrypted message")


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Python client for the Laravel short-poll messaging API")
    parser.add_argument("--base-url", required=True, help="Base URL of the API, e.g., http://localhost:8000")
    parser.add_argument("--creds", type=Path, required=True, help="Path to store/load client credentials JSON")
    parser.add_argument("--register", action="store_true", help="Register a new client and save credentials")
    parser.add_argument("--name", help="Optional name when registering")
    parser.add_argument("--poll", action="store_true", help="Start the polling loop")
    parser.add_argument("--send", dest="send_text", help="Send a plaintext message (will be encrypted)")
    parser.add_argument("--send-type", default="client", help="Message type field for outbound messages")
    parser.add_argument("--to-client-id", action="append", dest="recipients", default=[], help="Client IDs to fan-out to")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    creds = load_credentials(args.creds)

    if args.register or creds is None:
        creds = register_client(args.base_url, args.name, args.creds)

    if args.send_text:
        send_message(args.base_url, creds, args.send_text, args.send_type, args.recipients)

    if args.poll:
        poll_loop(args.base_url, creds)


if __name__ == "__main__":
    main()
