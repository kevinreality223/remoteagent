from __future__ import annotations

import re
import time
from typing import Dict, Optional

from requests import HTTPError, RequestException

from . import api
from .handlers import MessageHandler


def discard_pending_messages(base_url: str, creds: Dict[str, str]) -> Optional[str]:
    """Ack and discard any messages currently queued for the client.

    Returns the ID of the last message that was acknowledged so the caller can
    continue polling from that cursor.
    """

    cursor: Optional[str] = None
    while True:
        messages = api.poll_once(base_url, creds, cursor)
        if not messages:
            break
        cursor = messages[-1]["id"]
        api.ack_messages(base_url, creds, cursor)

    return cursor


def poll_loop(
    base_url: str, creds: Dict[str, str], handler: MessageHandler, start_cursor: Optional[str] = None
) -> None:
    cursor: Optional[str] = start_cursor
    interval = 3
    while True:
        try:
            messages = api.poll_once(base_url, creds, cursor)
            if not messages:
                interval = min(interval + 3, 30)
                print(f"No messages. Next poll in {interval}s")
            else:
                last_id = None
                for msg in messages:
                    cursor = msg["id"]
                    last_id = cursor
                    try:
                        handler.handle(msg, creds)
                    except Exception as exc:  # pylint: disable=broad-except
                        detail = str(exc).strip() or exc.__class__.__name__
                        print(f"Error handling message {cursor}: {detail}\n{exc}")
                if last_id is not None:
                    api.ack_messages(base_url, creds, last_id)
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
            detail = str(exc).strip() or exc.__class__.__name__
            print(f"Error during poll (status {status}): {detail}. Retrying in {interval}s")
            if hint:
                print(hint)
            elif response is not None and response.text:
                snippet = response.text.strip()
                if re.search(r"<\s*(?:!doctype|html|head|body)\b", snippet, re.IGNORECASE):
                    comment_match = re.search(r"<!--\s*(.*?)\s*-->", snippet, re.DOTALL)
                    if comment_match and comment_match.group(1):
                        snippet = comment_match.group(1).strip()
                    else:
                        snippet = re.sub(r"<[^>]+>", " ", snippet)
                snippet = " ".join(snippet.split())
                snippet = snippet if len(snippet) <= 300 else f"{snippet[:297]}..."
                print(f"Server response: {snippet}")
        except RequestException as exc:
            interval = min(interval + 3, 30)
            detail = str(exc).strip() or exc.__class__.__name__
            print(
                "Error during poll: {0}. Retrying in {1}s. "
                "Confirm the server is running at {2}.".format(detail, interval, base_url)
            )
        except Exception as exc:  # pylint: disable=broad-except
            interval = min(interval + 3, 30)
            detail = str(exc).strip() or exc.__class__.__name__
            print(f"Error during poll: {detail}. Retrying in {interval}s")
        time.sleep(interval)
