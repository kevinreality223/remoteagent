from __future__ import annotations

import time
from typing import Dict, Optional

from requests import HTTPError, RequestException

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
        except HTTPError as exc:
            interval = min(interval + 3, 30)
            response = exc.response
            status = response.status_code if response is not None else "unknown"
            hint = ""
            if isinstance(status, int):
                if status == 401:
                    hint = (
                        "Authentication failed. Stored credentials may be stale; "
                        "delete the credentials file and re-run to re-register."
                    )
                elif status >= 500:
                    body = (response.text or "").lower() if response is not None else ""
                    if "no such table" in body or ("table" in body and "exists" not in body):
                        hint = (
                            "The server reported a database error. Ensure migrations have "
                            "been run on the server (php artisan migrate)."
                        )
            print(f"Error during poll (status {status}): {exc}. Retrying in {interval}s")
            if hint:
                print(hint)
        except RequestException as exc:
            interval = min(interval + 3, 30)
            print(
                "Error during poll: {0}. Retrying in {1}s. "
                "Confirm the server is running at {2}.".format(exc, interval, base_url)
            )
        except Exception as exc:  # pylint: disable=broad-except
            interval = min(interval + 3, 30)
            print(f"Error during poll: {exc}. Retrying in {interval}s")
        time.sleep(interval)
