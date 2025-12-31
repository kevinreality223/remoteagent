from __future__ import annotations

import sys

from . import api
from .conf import BASE_URL, CLIENT_NAME, CREDENTIALS_PATH
from .handlers import ConsoleMessageHandler
from .identity import machine_display_name, machine_fingerprint
from .polling import discard_pending_messages, poll_loop
from .storage import load_credentials, save_credentials


def ensure_registration() -> dict:
    fingerprint = machine_fingerprint()
    client_name = CLIENT_NAME or machine_display_name()
    creds = load_credentials(CREDENTIALS_PATH)

    if creds is None or creds.get("fingerprint") != fingerprint:
        try:
            creds = api.register_client(BASE_URL, fingerprint, client_name)
        except Exception as exc:  # noqa: BLE001 - surfacing underlying error to the operator
            print("Unable to register client with the server:")
            print(exc)
            print("\nPlease verify the server is running and migrations are applied, then retry.")
            sys.exit(1)

        save_credentials(CREDENTIALS_PATH, creds)
        print(f"Registered client {creds['client_id']} and saved credentials to {CREDENTIALS_PATH}")
    else:
        print(f"Using existing credentials at {CREDENTIALS_PATH}")

    return creds


def run() -> None:
    creds = ensure_registration()

    cursor = discard_pending_messages(BASE_URL, creds)
    if cursor:
        print("Cleared pending messages on startup.")

    handler = ConsoleMessageHandler(BASE_URL)

    # The client polls indefinitely. Sending can be layered on top of the
    # handler structure; for now we focus on keeping the client online and
    # receptive to new messages.
    poll_loop(BASE_URL, creds, handler, start_cursor=cursor)


if __name__ == "__main__":
    run()
