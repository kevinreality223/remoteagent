from __future__ import annotations

import sys

from . import api
from .conf import BASE_URL, CLIENT_NAME, CREDENTIALS_PATH
from .handlers import ConsoleMessageHandler
from .identity import machine_fingerprint
from .polling import poll_loop
from .storage import load_credentials, save_credentials


def ensure_registration() -> dict:
    fingerprint = machine_fingerprint()
    creds = load_credentials(CREDENTIALS_PATH)

    if creds is None or creds.get("fingerprint") != fingerprint:
        try:
            creds = api.register_client(BASE_URL, fingerprint, CLIENT_NAME)
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

    handler = ConsoleMessageHandler()

    # The client polls indefinitely. Sending can be layered on top of the
    # handler structure; for now we focus on keeping the client online and
    # receptive to new messages.
    poll_loop(BASE_URL, creds, handler)


if __name__ == "__main__":
    run()
