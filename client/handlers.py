from __future__ import annotations

import json
from datetime import datetime, timezone
from typing import Any, Dict

from . import api


class MessageHandler:
    def handle(self, message: Dict[str, Any], creds: Dict[str, str]) -> None:  # pragma: no cover - interface
        print(message)


class ConsoleMessageHandler(MessageHandler):
    def __init__(self, base_url: str) -> None:
        self.base_url = base_url

    def handle(self, message: Dict[str, Any], creds: Dict[str, str]) -> None:
        plaintext = api.decrypt_message(creds, message)
        print(f"[received #{message['id']}] {json.dumps(plaintext)}")

        if plaintext.get("type") == "test":
            response_payload = {
                "type": "test_response",
                "payload": {"message": "work", "in_reply_to": message.get("id")},
                "timestamp": datetime.now(timezone.utc).isoformat(),
            }
            api.send_message(self.base_url, creds, response_payload)
            print("Sent automated response for test message.")
