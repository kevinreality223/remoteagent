from __future__ import annotations

import json
from typing import Any, Dict

from . import api


class MessageHandler:
    def handle(self, message: Dict[str, Any], creds: Dict[str, str]) -> None:  # pragma: no cover - interface
        print(message)


class ConsoleMessageHandler(MessageHandler):
    def handle(self, message: Dict[str, Any], creds: Dict[str, str]) -> None:
        plaintext = api.decrypt_message(creds, message)
        print(f"[received #{message['id']}] {json.dumps(plaintext)}")
