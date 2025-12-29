from __future__ import annotations

from typing import Any, Dict, List, Optional

import requests

from .crypto import decrypt_payload, encrypt_payload


def auth_headers(creds: Dict[str, str]) -> Dict[str, str]:
    return {
        "Authorization": f"Bearer {creds['api_token']}",
        "X-Client-Id": creds["client_id"],
    }


def register_client(base_url: str, fingerprint: str, name: Optional[str]) -> Dict[str, str]:
    response = requests.post(
        f"{base_url}/api/v1/clients/register",
        json={"name": name, "fingerprint": fingerprint} if name else {"fingerprint": fingerprint},
        timeout=10,
    )
    response.raise_for_status()
    data = response.json()
    data["fingerprint"] = fingerprint
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


def decrypt_message(creds: Dict[str, str], message: Dict[str, Any]) -> Dict[str, Any]:
    aad = {
        "to": creds["client_id"],
        "ts": message["created_at"],
    }
    return decrypt_payload(creds["personal_token"], message, aad)


def send_message(base_url: str, creds: Dict[str, str], plaintext: Dict[str, Any]) -> None:
    timestamp = plaintext.get("timestamp")
    aad = {
        "from": creds["client_id"],
        "ts": timestamp,
    }
    encrypted = encrypt_payload(creds["personal_token"], plaintext, aad)
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
