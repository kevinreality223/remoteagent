from __future__ import annotations

import time
from typing import Dict, Optional

from . import api
from .handlers import MessageHandler


def poll_loop(base_url: str, creds: Dict[str, str], handler: MessageHandler) -> None:
    cursor: Optional[str] = None
    interval = 3
    while True:
        try:
            messages = api.poll_once(base_url, creds, cursor)
            if not messages:
                interval = min(interval + 3, 30)
                print(f"No messages. Next poll in {interval}s")
            else:
                for msg in messages:
                    handler.handle(msg, creds)
                    cursor = msg["id"]
                api.ack_messages(base_url, creds, cursor)
                interval = 3
        except Exception as exc:  # pylint: disable=broad-except
            interval = min(interval + 3, 30)
            print(f"Error during poll: {exc}. Retrying in {interval}s")
        time.sleep(interval)
